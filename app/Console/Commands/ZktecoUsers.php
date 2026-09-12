<?php

namespace App\Console\Commands;

use App\Models\DeviceCommand;
use App\Models\DeviceUser;
use App\Models\Setting;
use App\Services\Zkteco\ZktecoClient;
use Illuminate\Console\Command;

/**
 * Show the users stored on the device.
 *
 * Push-mode devices (SenseFace): the table shows the last USERINFO dump
 * the device pushed. Use --query to queue a fresh dump — the device
 * uploads its full user list on the next /iclock/getrequest poll.
 *
 * Pull-capable devices: --socket reads the user table over UDP 4370.
 */
class ZktecoUsers extends Command
{
    protected $signature = 'zkteco:users
                            {--query : Queue a fresh USERINFO dump from the device}
                            {--sn= : Target device serial (blank = any device)}
                            {--socket : Read directly via UDP 4370 (pull-mode devices)}
                            {--ip= : Device IP (socket mode)}
                            {--port= : Device port (socket mode)}';

    protected $description = 'List users on the ZKTeco device';

    public function handle(): int
    {
        if ($this->option('socket')) {
            return $this->socketMode();
        }

        if ($this->option('query')) {
            DeviceCommand::create([
                'device_sn' => $this->option('sn'),
                'command' => 'DATA QUERY USERINFO',
            ]);
            $this->info('Queued: user list dump — the device pushes USERINFO on its next poll.');
            $this->line('Run this command again after a minute to see the result.');
        }

        $users = DeviceUser::orderBy('pin')->get();

        if ($users->isEmpty()) {
            $this->warn('No device users synced yet. Run with --query to request a dump.');
            return self::SUCCESS;
        }

        $privileges = array_flip(ZktecoClient::PRIVILEGES);

        $this->table(
            ['PIN', 'Name', 'Card', 'Privilege (Role)', 'Group', 'Device'],
            $users->map(fn ($u) => [
                $u->pin,
                $u->name ?: '—',
                $u->card ?: '—',
                ($privileges[$u->privilege] ?? $u->privilege) . " ({$u->privilege})",
                $u->group,
                $u->device_sn ?? '—',
            ])->all()
        );

        $this->info($users->count() . ' device user(s).');

        return self::SUCCESS;
    }

    private function socketMode(): int
    {
        $client = new ZktecoClient(
            $this->option('ip') ?: Setting::get('zkteco_ip', '192.168.1.201'),
            (int) ($this->option('port') ?: Setting::get('zkteco_port', 4370)),
            (int) Setting::get('zkteco_timeout', 5),
        );

        try {
            $users = $client->getUsers();
            $client->disconnect();
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

        return self::SUCCESS;
    }
}
