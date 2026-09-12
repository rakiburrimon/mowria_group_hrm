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

        $parser = new \App\Services\Zkteco\DeviceDataParser();

        if ($table === 'ATTLOG') {
            $parser->importAttendance($body, $sn);

            activity()->withProperties(['sn' => $sn])->log('zkteco attendance pushed');
        } elseif ($table === 'USERINFO') {
            $parser->importUsers($body, $sn);
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

}
