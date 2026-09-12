<?php

namespace App\Http\Controllers;

use App\Models\DeviceCommand;
use App\Models\DeviceLog;
use App\Models\DeviceUser;
use App\Models\Employee;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco iclock push-protocol endpoints.
 *
 * The SenseFace/SpeedFace terminals don't expose a pull API — they PUSH
 * attendance and status data to these URLs over HTTP in real time.
 *
 * Device side: Menu → COMM → Cloud Server → Server = this host, Port = web port.
 */
class IclockController extends Controller
{
    /**
     * GET /iclock/cdata?SN=..&options=all — handshake.
     *
     * The device asks for its config; we reply with the push parameters.
     */
    public function handshake(Request $request): Response
    {
        $sn = $request->query('SN', 'unknown');

        $config = implode("\n", [
            "GET OPTION FROM: {$sn}",
            'Stamp=' . time(),
            'OpStamp=' . time(),
            'PhotoStamp=' . time(),
            'ErrorDelay=30',        // seconds between retries after errors
            'Delay=10',             // seconds between data pushes
            'TransTimes=00:00;14:05',
            'TransInterval=1',      // push interval (minutes)
            'TransFlag=1111000000', // ATTLOG enabled
            'TimeZone=6',           // GMT+6 (Asia/Dhaka)
            'Realtime=1',           // push punches instantly
            'Encrypt=0',
            '',
        ]);

        return response($config, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * POST /iclock/cdata?SN=..&table=.. — data pushes (attendance, op logs).
     */
    public function push(Request $request): Response
    {
        $sn = $request->query('SN');
        $table = strtoupper((string) $request->query('table'));
        $body = $request->getContent();

        Log::debug('iclock push', ['sn' => $sn, 'table' => $table, 'body' => substr($body, 0, 2000)]);

        if ($table === 'ATTLOG') {
            $this->storeAttendance($body, $sn);
        } elseif ($table === 'USERINFO') {
            $this->storeUsers($body, $sn);
        }

        // Any table we don't parse is still acked so the device stays happy
        return response('OK', 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * POST /iclock/registry — device registers itself on boot.
     */
    public function registry(Request $request): Response
    {
        $sn = $request->query('SN');

        activity()->withProperties(['sn' => $sn])
            ->log('zkteco device registered');

        return response("RegistryCode={$sn}\nOK", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * GET /iclock/getrequest — device polls for pending commands.
     * We hand out one queued command at a time: C:{id}:{command}
     */
    public function getRequest(Request $request): Response
    {
        $sn = $request->query('SN');

        $command = DeviceCommand::where('status', 'pending')
            ->where(fn ($q) => $q->where('device_sn', $sn)->orWhereNull('device_sn'))
            ->oldest()
            ->first();

        if (! $command) {
            return response('OK', 200, ['Content-Type' => 'text/plain']);
        }

        $command->update(['status' => 'sent']);

        return response("C:{$command->id}:{$command->command}", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * POST /iclock/devicecmd — device reports command results.
     * Body: ID={id}&Return=0&CMD=...
     */
    public function deviceCmd(Request $request): Response
    {
        $body = $request->getContent();
        $id = null;
        $ret = null;

        // Parse "ID=12&Return=0&..." or "ID=12\tReturn=0\t..."
        if (preg_match('/ID=(\d+)/', $body, $m)) {
            $id = (int) $m[1];
        }
        if (preg_match('/Return=(-?\d+)/', $body, $m)) {
            $ret = (int) $m[1];
        }

        if ($id && ($command = DeviceCommand::find($id))) {
            $command->update([
                'status' => $ret === 0 || $ret === null ? 'done' : 'failed',
                'response' => substr($body, 0, 2000),
            ]);

            activity()->withProperties([
                'command_id' => $id,
                'command' => $command->command,
                'result' => $command->status,
            ])->log('zkteco device command ' . $command->status);
        }

        return response('OK', 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * GET /iclock/querydata / ping — plain heartbeat.
     */
    public function ping(): Response
    {
        return response('OK', 200, ['Content-Type' => 'text/plain']);
    }

    // ------------------------------------------------------------------

    /**
     * Parse ATTLOG push body into device_logs rows, then fold them
     * into the attendances table.
     *
     * Line formats seen on iclock firmware:
     *   PIN=101\tAttTime=2026-09-12 08:30:15\tStatus=0\tVerify=1\tWorkcode=0
     *   101\t2026-09-12 08:30:15\t0\t1\t0
     */
    private function storeAttendance(string $body, ?string $sn): void
    {
        $punches = [];

        foreach (preg_split('/\r?\n/', trim($body)) as $line) {
            if ($line === '') {
                continue;
            }

            $fields = preg_split('/\t/', $line);

            // key=value format
            if (str_contains($fields[0], '=')) {
                $kv = [];
                foreach ($fields as $f) {
                    [$k, $v] = array_pad(explode('=', $f, 2), 2, '');
                    $kv[strtolower(trim($k))] = trim($v);
                }
                $pin = (int) ($kv['pin'] ?? 0);
                $time = $kv['atttime'] ?? $kv['time'] ?? null;
                $status = (int) ($kv['status'] ?? 0);
                $verify = (int) ($kv['verify'] ?? $kv['verifycode'] ?? 0);
                $workcode = (int) ($kv['workcode'] ?? 0);
            } else {
                // positional: pin, time, status, verify, workcode
                $pin = (int) ($fields[0] ?? 0);
                $time = $fields[1] ?? null;
                $status = (int) ($fields[2] ?? 0);
                $verify = (int) ($fields[3] ?? 0);
                $workcode = (int) ($fields[4] ?? 0);
            }

            if (! $pin || ! $time) {
                continue;
            }

            $punches[] = [
                'pin' => $pin,
                'punch_time' => Carbon::parse($time)->format('Y-m-d H:i:s'),
                'status' => $status,
                'verify' => $verify,
                'workcode' => $workcode,
                'device_sn' => $sn,
                'processed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($punches)) {
            return;
        }

        // Dedup via the unique index — ignore collisions
        DB::table('device_logs')->insertOrIgnore($punches);

        // Fold today's (and the pushed dates') punches into attendances
        $this->processPunches(collect($punches)->pluck('punch_time')->map(fn ($t) => substr($t, 0, 10))->unique());

        activity()->withProperties([
            'sn' => $sn,
            'punches' => count($punches),
        ])->log('zkteco attendance pushed');
    }

    /**
     * Parse a USERINFO push — the device dumps its user table after a
     * `DATA QUERY USERINFO` command (or a manual upload).
     *
     * Line formats: key=value tab-separated, e.g.
     *   PIN=101\tName=John\tPri=0\tPasswd=\tCard=12345\tGrp=1\tTZ=...
     * or positional: pin, name, privilege, password, card, group
     */
    private function storeUsers(string $body, ?string $sn): void
    {
        $count = 0;

        foreach (preg_split('/\r?\n/', trim($body)) as $line) {
            if ($line === '') {
                continue;
            }

            $fields = preg_split('/\t/', $line);

            if (str_contains($fields[0], '=')) {
                $kv = [];
                foreach ($fields as $f) {
                    [$k, $v] = array_pad(explode('=', $f, 2), 2, '');
                    $kv[strtolower(trim($k))] = trim($v);
                }
                $data = [
                    'pin' => (int) ($kv['pin'] ?? 0),
                    'name' => $kv['name'] ?? null,
                    'privilege' => (int) ($kv['pri'] ?? $kv['privilege'] ?? 0),
                    'password' => $kv['passwd'] ?? $kv['password'] ?? null,
                    'card' => (int) ($kv['card'] ?? 0),
                    'group' => (int) ($kv['grp'] ?? $kv['group'] ?? 0),
                ];
            } else {
                $data = [
                    'pin' => (int) ($fields[0] ?? 0),
                    'name' => $fields[1] ?? null,
                    'privilege' => (int) ($fields[2] ?? 0),
                    'password' => $fields[3] ?? null,
                    'card' => (int) ($fields[4] ?? 0),
                    'group' => (int) ($fields[5] ?? 0),
                ];
            }

            if (! $data['pin']) {
                continue;
            }

            DeviceUser::updateOrCreate(
                ['pin' => $data['pin'], 'device_sn' => $sn],
                $data
            );
            $count++;
        }

        if ($count) {
            activity()->withProperties(['sn' => $sn, 'users' => $count])
                ->log('zkteco device user list synced');
        }
    }

    /**
     * Rebuild attendances from raw device punches for the given dates.
     */
    private function processPunches($dates): void
    {
        $workStart = Carbon::parse(Setting::get('work_start_time', '09:00'));
        $grace = (int) Setting::get('late_grace_minutes', 15);

        $employees = Employee::all()->keyBy('device_user_id');

        foreach ($dates as $date) {
            $punches = DeviceLog::whereDate('punch_time', $date)->get()
                ->groupBy('pin');

            foreach ($punches as $pin => $rows) {
                $employee = $employees->get($pin)
                    ?? Employee::where('employee_id', (string) $pin)->first();

                if (! $employee) {
                    continue;
                }

                $times = $rows->pluck('punch_time')->map->format('H:i')->sort()->values();
                $checkIn = $times->first();
                $checkOut = $times->count() > 1 ? $times->last() : null;
                $status = Carbon::parse($checkIn)->gt($workStart->copy()->addMinutes($grace))
                    ? \App\Models\Attendance::STATUS_LATE
                    : \App\Models\Attendance::STATUS_PRESENT;

                \App\Models\Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date],
                    [
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'status' => $status,
                        'notes' => 'Synced from ZKTeco device',
                    ]
                );

                $rows->each->update(['processed' => true]);
            }
        }
    }
}
