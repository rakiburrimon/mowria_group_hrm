<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Zkteco\ZktecoClient;
use Illuminate\Console\Command;

/**
 * Delete a user from the ZKTeco device by PIN.
 *
 * Usage:
 *   php artisan zkteco:user-delete --pin=101
 */
class ZktecoDeleteUser extends Command
{
    protected $signature = 'zkteco:user-delete
                            {--pin= : Numeric PIN of the device user to delete}
                            {--ip= : Device IP}
                            {--port= : Device port}';

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

        $client = new ZktecoClient(
            $this->option('ip') ?: Setting::get('zkteco_ip', '192.168.1.201'),
            (int) ($this->option('port') ?: Setting::get('zkteco_port', 4370)),
            (int) Setting::get('zkteco_timeout', 5),
        );

        try {
            $client->deleteUser((int) $pin);
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("User {$pin} deleted from the device.");

        activity()->withProperties(['pin' => (int) $pin])
            ->log('zkteco device user deleted');

        $client->disconnect();

        return self::SUCCESS;
    }
}
