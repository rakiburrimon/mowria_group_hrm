<?php

namespace App\Console\Commands;

use App\Models\DeviceCommand;
use App\Models\Employee;
use App\Services\Zkteco\ZktecoClient;
use Illuminate\Console\Command;

/**
 * Create or update a user on the ZKTeco device, with a privilege ("role").
 *
 * Two paths:
 *  - default: queue a command — the device picks it up on its next
 *    /iclock/getrequest poll (push-mode devices like SenseFace 2A)
 *  - --socket: write directly over UDP 4370 (pull-capable devices)
 *
 * Usage:
 *   php artisan zkteco:user --pin=101 --name="John Doe" --privilege=user
 *   php artisan zkteco:user --employee=EMP001
 *   php artisan zkteco:user --pin=101 --name="John" --socket --ip=192.168.1.201
 *
 * Privileges: user (0), enroller (2), manager (6), admin (14)
 */
class ZktecoSetUser extends Command
{
    protected $signature = 'zkteco:user
                            {--pin= : Numeric PIN / user ID on the device}
                            {--employee= : Employee code — uses its device_user_id as PIN}
                            {--name= : Display name}
                            {--password= : Device password (max 8 chars)}
                            {--card= : RFID card number}
                            {--privilege=user : user|enroller|manager|admin}
                            {--sn= : Target device serial (blank = any device)}
                            {--socket : Write directly via UDP 4370 instead of queueing}
                            {--ip= : Device IP (socket mode)}
                            {--port= : Device port (socket mode)}';

    protected $description = 'Add or update a user on the ZKTeco device';

    public function handle(): int
    {
        $pin = $this->option('pin');
        $name = $this->option('name') ?? '';

        // Pull pin/name from the employee record when --employee is used
        if ($code = $this->option('employee')) {
            $employee = Employee::where('employee_id', $code)->first();

            if (! $employee) {
                $this->error("Employee '{$code}' not found.");

                return self::FAILURE;
            }

            $pin = $employee->device_user_id ?? $employee->id;
            $name = $name ?: $employee->full_name;
        }

        if (! $pin || ! is_numeric($pin)) {
            $this->error('A numeric --pin (or --employee with device_user_id) is required.');

            return self::FAILURE;
        }

        $privilege = ZktecoClient::PRIVILEGES[$this->option('privilege')] ?? null;

        if ($privilege === null) {
            $this->error('Invalid privilege. Use: ' . implode(', ', array_keys(ZktecoClient::PRIVILEGES)));

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?? '');
        $card = (int) ($this->option('card') ?? 0);

        if ($this->option('socket')) {
            return $this->socketMode((int) $pin, $name, $password, $card, $privilege);
        }

        // Queue an iclock command — the device fetches it on its next poll
        DeviceCommand::create([
            'device_sn' => $this->option('sn'),
            'command' => "DATA UPDATE USERINFO PIN={$pin}\tName={$name}\tPri={$privilege}\tPasswd={$password}\tCard={$card}\tGrp=1\tTZ=0000000100000000",
        ]);

        $this->info("Queued: user {$pin} ({$name}, privilege '{$this->option('privilege')}') — applied on the device's next poll.");

        activity()->withProperties([
            'pin' => (int) $pin,
            'name' => $name,
            'privilege' => $privilege,
        ])->log('zkteco device user queued');

        return self::SUCCESS;
    }

    private function socketMode(int $pin, string $name, string $password, int $card, int $privilege): int
    {
        $client = new ZktecoClient(
            $this->option('ip') ?: \App\Models\Setting::get('zkteco_ip', '192.168.1.201'),
            (int) ($this->option('port') ?: \App\Models\Setting::get('zkteco_port', 4370)),
            (int) \App\Models\Setting::get('zkteco_timeout', 5),
        );

        try {
            $client->setUser($pin, $name, $password, $card, $privilege);
            $client->disconnect();
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("User {$pin} ({$name}) written to device with privilege '{$this->option('privilege')}'.");

        return self::SUCCESS;
    }
}
