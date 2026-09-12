<?php

namespace App\Console\Commands;

use App\Models\DeviceCommand;
use App\Models\Setting;
use App\Services\Zkteco\ZktecoClient;
use Illuminate\Console\Command;

/**
 * Delete a user from the ZKTeco device by PIN.
 *
 * Default: queues a DATA DELETE USERINFO command the device fetches on its
 * next /iclock/getrequest poll. --socket deletes directly over UDP 4370.
 *
 * Usage:
 *   php artisan zkteco:user-delete --pin=101
 *   php artisan zkteco:user-delete --pin=101 --socket --ip=192.168.1.201
 */
class ZktecoDeleteUser extends Command
{
    protected $signature = 'zkteco:user-delete
                            {--pin= : Numeric PIN of the device user to delete}
                            {--sn= : Target device serial (blank = any device)}
                            {--socket : Delete directly via UDP 4370 instead of queueing}
                            {--ip= : Device IP (socket mode)}
                            {--port= : Device port (socket mode)}';

    protected $description = 'Delete a user from the ZKTeco device';

    public function handle(): int
    {
        $pin = $this->option('pin');

        if (! $pin || ! is_numeric($pin)) {
            $this->error('A numeric --pin is required.');

            return self::FAILURE;
        }

        if (! $this->confirm("Delete device user {$pin}?", true)) {
            return self::SUCCESS;
        }

        if ($this->option('socket')) {
            return $this->socketMode((int) $pin);
        }

        DeviceCommand::create([
            'device_sn' => $this->option('sn'),
            'command' => "DATA DELETE USERINFO PIN={$pin}",
        ]);

        $this->info("Queued: delete user {$pin} — applied on the device's next poll.");

        activity()->withProperties(['pin' => (int) $pin])
            ->log('zkteco device user delete queued');

        return self::SUCCESS;
    }

    private function socketMode(int $pin): int
    {
        $client = new ZktecoClient(
            $this->option('ip') ?: Setting::get('zkteco_ip', '192.168.1.201'),
            (int) ($this->option('port') ?: Setting::get('zkteco_port', 4370)),
            (int) Setting::get('zkteco_timeout', 5),
        );

        try {
            $client->deleteUser($pin);
            $client->disconnect();
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("User {$pin} deleted from the device.");

        return self::SUCCESS;
    }
}
