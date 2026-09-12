<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Setting;
use App\Services\Zkteco\ZktecoClient;
use Illuminate\Console\Command;

/**
 * Create or update a user on the ZKTeco device, with a privilege ("role").
 *
 * Usage:
 *   php artisan zkteco:user --pin=101 --name="John Doe" --privilege=user
 *   php artisan zkteco:user --pin=101 --name="John Doe" --card=123456 --privilege=manager
 *   php artisan zkteco:user --employee=EMP001                 # pull pin/name from employee
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
                            {--ip= : Device IP}
                            {--port= : Device port}';

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

        $client = new ZktecoClient(
            $this->option('ip') ?: Setting::get('zkteco_ip', '192.168.1.201'),
            (int) ($this->option('port') ?: Setting::get('zkteco_port', 4370)),
            (int) Setting::get('zkteco_timeout', 5),
        );

        try {
            $client->setUser(
                (int) $pin,
                $name,
                (string) ($this->option('password') ?? ''),
                (int) ($this->option('card') ?? 0),
                $privilege,
            );
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("User {$pin} ({$name}) written to device with privilege '{$this->option('privilege')}'.");

        // Audit trail
        activity()->withProperties([
            'pin' => (int) $pin,
            'name' => $name,
            'privilege' => $privilege,
        ])->log('zkteco device user created/updated');

        $client->disconnect();

        return self::SUCCESS;
    }
}
