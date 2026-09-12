<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\Zkteco\ZktecoClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pull attendance logs from a ZKTeco terminal (SenseFace 2A etc.)
 * over UDP port 4370 and sync them into the attendances table.
 *
 * Usage:
 *   php artisan attendance:pull
 *   php artisan attendance:pull --ip=192.168.31.210 --port=4370
 *   php artisan attendance:pull --dry          # preview without writing
 *   php artisan attendance:pull --clear        # clear device logs after sync
 */
class ZktecoPullAttendance extends Command
{
    protected $signature = 'attendance:pull
                            {--ip= : Device IP (defaults to zkteco_ip setting)}
                            {--port= : Device port (defaults to zkteco_port setting)}
                            {--dry : Show what would be synced without writing}
                            {--clear : Clear the attendance log on the device after a successful sync}';

    protected $description = 'Pull attendance logs from the ZKTeco device and sync them into the database';

    public function handle(): int
    {
        $ip = $this->option('ip') ?: Setting::get('zkteco_ip', '192.168.31.210');
        $port = (int) ($this->option('port') ?: Setting::get('zkteco_port', 4370));
        $timeout = (int) Setting::get('zkteco_timeout', 5);

        $this->info("Connecting to ZKTeco device at {$ip}:{$port} …");

        $client = new ZktecoClient($ip, $port, $timeout);

        try {
            $logs = $client->getAttendance();
        } catch (\Throwable $e) {
            $this->error('Failed to pull attendance: ' . $e->getMessage());
            Log::error('attendance:pull failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info(count($logs) . ' log entries received.');

        if (empty($logs)) {
            return self::SUCCESS;
        }

        // Map device PIN → employee (device_user_id, or numeric employee_id as fallback)
        $employeeByPin = Employee::all()->keyBy('device_user_id');
        $employeeByCode = Employee::all()->keyBy('employee_id');

        // Group punches by employee + date
        $grouped = [];
        $unmapped = [];

        foreach ($logs as $log) {
            $employee = $employeeByPin->get($log['pin'])
                ?? (is_numeric($log['pin']) ? $employeeByCode->get((string) $log['pin']) : null);

            if (! $employee) {
                $unmapped[$log['pin']] = true;
                continue;
            }

            $ts = Carbon::parse($log['timestamp']);
            $key = $employee->id . '|' . $ts->format('Y-m-d');
            $grouped[$key]['employee_id'] = $employee->id;
            $grouped[$key]['date'] = $ts->format('Y-m-d');
            $grouped[$key]['times'][] = $ts->format('H:i');
        }

        if ($unmapped) {
            $this->warn('Unmapped device PINs (no employee match): ' . implode(', ', array_keys($unmapped)));
        }

        $this->table(
            ['Employee', 'Date', 'First In', 'Last Out'],
            collect($grouped)->map(fn ($g) => [
                $employeeByPin->firstWhere('id', $g['employee_id'])?->full_name
                    ?? $employeeByCode->firstWhere('id', $g['employee_id'])?->full_name
                    ?? '#' . $g['employee_id'],
                $g['date'],
                min($g['times']),
                count($g['times']) > 1 ? max($g['times']) : '-',
            ])->values()->all()
        );

        if ($this->option('dry')) {
            $this->comment('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($grouped, &$created, &$updated) {
            // Late if first punch is past work start + grace minutes
            $workStart = Carbon::parse(Setting::get('work_start_time', '09:00'));
            $grace = (int) Setting::get('late_grace_minutes', 15);

            foreach ($grouped as $g) {
                $checkIn = min($g['times']);
                $checkOut = count($g['times']) > 1 ? max($g['times']) : null;
                $status = Carbon::parse($checkIn)->gt($workStart->copy()->addMinutes($grace))
                    ? Attendance::STATUS_LATE
                    : Attendance::STATUS_PRESENT;

                $attendance = Attendance::updateOrCreate(
                    ['employee_id' => $g['employee_id'], 'date' => $g['date']],
                    [
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'status' => $status,
                        'notes' => 'Synced from ZKTeco device',
                    ]
                );

                $attendance->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        $this->info("Synced {$created} new / {$updated} updated attendance record(s).");

        // Audit trail — no auth user in CLI, logged as System
        activity()->withProperties([
            'device' => "{$ip}:{$port}",
            'logs' => count($logs),
            'created' => $created,
            'updated' => $updated,
        ])->log('attendance pulled from ZKTeco device');

        if ($this->option('clear')) {
            try {
                $client->clearAttendance();
                $this->info('Device attendance log cleared.');
            } catch (\Throwable $e) {
                $this->warn('Sync succeeded but clearing the device log failed: ' . $e->getMessage());
            }
        }

        $client->disconnect();

        return self::SUCCESS;
    }
}
