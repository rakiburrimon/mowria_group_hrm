<?php

namespace App\Services\Zkteco;

use App\Models\Attendance;
use App\Models\DeviceLog;
use App\Models\DeviceUser;
use App\Models\Employee;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Parses ZKTeco device data — shared by the iclock push endpoints and
 * the USB file importer. Handles both line formats:
 *   key=value: PIN=101\tAttTime=...\tStatus=0\tVerify=15
 *   positional: 101\t2026-09-12 08:30:15\t0\t15\t0
 */
class DeviceDataParser
{
    /**
     * Parse an attendance-log body/dump into device_logs rows and fold
     * them into attendances. Returns the number of punches parsed.
     */
    public function importAttendance(string $body, ?string $sn = null): int
    {
        $punches = [];

        foreach (preg_split('/\r?\n/', trim($body)) as $line) {
            if ($line === '') {
                continue;
            }

            $f = $this->fields($line);

            if (isset($f['_kv'])) {
                $pin = (int) ($f['pin'] ?? 0);
                $time = $f['atttime'] ?? $f['time'] ?? null;
                $status = (int) ($f['status'] ?? 0);
                $verify = (int) ($f['verify'] ?? $f['verifycode'] ?? 0);
                $workcode = (int) ($f['workcode'] ?? 0);
            } else {
                $pin = (int) ($f[0] ?? 0);
                $time = $f[1] ?? null;
                $status = (int) ($f[2] ?? 0);
                $verify = (int) ($f[3] ?? 0);
                $workcode = (int) ($f[4] ?? 0);
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
            return 0;
        }

        DB::table('device_logs')->insertOrIgnore($punches);

        // Fold into attendances for every date seen
        $dates = collect($punches)->pluck('punch_time')->map(fn ($t) => substr($t, 0, 10))->unique();
        $this->processPunches($dates);

        return count($punches);
    }

    /**
     * Parse a USERINFO body/dump into device_users rows. Returns count.
     */
    public function importUsers(string $body, ?string $sn = null): int
    {
        $count = 0;

        foreach (preg_split('/\r?\n/', trim($body)) as $line) {
            if ($line === '') {
                continue;
            }

            $f = $this->fields($line);

            if (isset($f['_kv'])) {
                $data = [
                    'pin' => (int) ($f['pin'] ?? 0),
                    'name' => $f['name'] ?? null,
                    'privilege' => (int) ($f['pri'] ?? $f['privilege'] ?? 0),
                    'password' => $f['passwd'] ?? $f['password'] ?? null,
                    'card' => (int) ($f['card'] ?? 0),
                    'group' => (int) ($f['grp'] ?? $f['group'] ?? 0),
                ];
            } else {
                $data = [
                    'pin' => (int) ($f[0] ?? 0),
                    'name' => $f[1] ?? null,
                    'privilege' => (int) ($f[2] ?? 0),
                    'password' => $f[3] ?? null,
                    'card' => (int) ($f[4] ?? 0),
                    'group' => (int) ($f[5] ?? 0),
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

        return $count;
    }

    /**
     * Split a line on tabs; if the first field has '=' mark it key=value.
     */
    private function fields(string $line): array
    {
        $fields = preg_split('/\t/', $line);

        if (str_contains($fields[0] ?? '', '=')) {
            $kv = ['_kv' => true];
            foreach ($fields as $f) {
                [$k, $v] = array_pad(explode('=', $f, 2), 2, '');
                $kv[strtolower(trim($k))] = trim($v);
            }
            return $kv;
        }

        return $fields;
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
            $punches = DeviceLog::whereDate('punch_time', $date)->get()->groupBy('pin');

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
                    ? Attendance::STATUS_LATE
                    : Attendance::STATUS_PRESENT;

                Attendance::updateOrCreate(
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
