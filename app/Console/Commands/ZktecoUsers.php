<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Zkteco\ZktecoClient;
use Illuminate\Console\Command;

/**
 * List all users stored on the ZKTeco device.
 *
 * Usage:
 *   php artisan zkteco:users
 *   php artisan zkteco:users --ip=192.168.31.210
 */
class ZktecoUsers extends Command
{
    protected $signature = 'zkteco:users
                            {--ip= : Device IP (defaults to zkteco_ip setting)}
                            {--port= : Device port (defaults to zkteco_port setting)}';

    protected $description = 'List users on the ZKTeco device';

    public function handle(): int
    {
        $client = $this->client();

        try {
            $users = $client->getUsers();
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $privileges = array_flip(ZktecoClient::PRIVILEGES);

        $this->table(
            ['PIN', 'Name', 'Card', 'Privilege (Role)', 'Group'],
            array_map(fn ($u) => [
                $u['uid'],
                $u['name'] ?: '—',
                $u['card'] ?: '—',
                ($privileges[$u['privilege']] ?? $u['privilege']) . " ({$u['privilege']})",
                $u['group'],
            ], $users)
        );

        $this->info(count($users) . ' user(s) on the device.');

        $client->disconnect();

        return self::SUCCESS;
    }

    private function client(): ZktecoClient
    {
        return new ZktecoClient(
            $this->option('ip') ?: Setting::get('zkteco_ip', '192.168.31.210'),
            (int) ($this->option('port') ?: Setting::get('zkteco_port', 4370)),
            (int) Setting::get('zkteco_timeout', 5),
        );
    }
}
