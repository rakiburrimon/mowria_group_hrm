<?php

namespace App\Http\Controllers;

use App\Models\DeviceCommand;
use App\Models\DeviceLog;
use App\Models\DeviceUser;
use App\Models\Employee;
use App\Services\Zkteco\ZktecoClient;
use Illuminate\Http\Request;

/**
 * Admin page for the attendance device — shows everything the device
 * pushed (users, punches) and the command queue, and lets admins queue
 * commands (user dump, user add/delete) for the next device poll.
 */
class DeviceController extends Controller
{
    /**
     * Show the device dashboard.
     */
    public function index()
    {
        return view('device.index', [
            'deviceUsers'  => DeviceUser::orderBy('pin')->get(),
            'deviceLogs'   => DeviceLog::latest('punch_time')->limit(100)->get(),
            'commands'     => DeviceCommand::latest()->limit(50)->get(),
            'lastPunchAt'  => DeviceLog::max('punch_time'),
            'knownDevices' => DeviceLog::whereNotNull('device_sn')->distinct()->pluck('device_sn'),
            'employees'    => Employee::orderBy('first_name')->get(),
        ]);
    }

    /**
     * Queue a USERINFO dump — the device pushes its user list next poll.
     */
    public function queryUsers(Request $request)
    {
        DeviceCommand::create([
            'device_sn' => $request->input('sn'),
            'command' => 'DATA QUERY USERINFO',
        ]);

        activity()->log('zkteco user list dump requested');

        return redirect()->route('device.index')
            ->with('success', 'User list dump queued — the device pushes it on its next poll.');
    }

    /**
     * Import a USB export file from the device — .dat/.txt dumps.
     * Auto-detects attendance logs vs user lists by content.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:dat,txt|max:10240',
        ]);

        $body = $request->file('file')->get();
        $parser = new \App\Services\Zkteco\DeviceDataParser();

        // Detect type: user dumps have Name=/Pri= or names; attlogs are mostly timestamps
        $first = strtok($body, "\r\n") ?: '';
        $isUsers = str_contains($first, 'Name=') || str_contains($first, 'Pri=');

        $count = $isUsers
            ? $parser->importUsers($body)
            : $parser->importAttendance($body);

        activity()->withProperties([
            'type' => $isUsers ? 'users' : 'attendance',
            'count' => $count,
            'file' => $request->file('file')->getClientOriginalName(),
        ])->log('zkteco data imported from file');

        return redirect()->route('device.index')->with(
            'success',
            "Imported {$count} " . ($isUsers ? 'user' : 'attendance') . " record(s)."
        );
    }

    /**
     * Queue a user create/update on the device.
     */
    public function setUser(Request $request)
    {
        $data = $request->validate([
            'pin' => 'required|integer|min:1',
            'name' => 'required|string|max:24',
            'privilege' => 'required|in:user,enroller,manager,admin',
            'password' => 'nullable|string|max:8',
            'card' => 'nullable|integer|min:0',
            'sn' => 'nullable|string',
        ]);

        $privilege = ZktecoClient::PRIVILEGES[$data['privilege']];

        DeviceCommand::create([
            'device_sn' => $data['sn'] ?? null,
            'command' => "DATA UPDATE USERINFO PIN={$data['pin']}\tName={$data['name']}\tPri={$privilege}\tPasswd=" . ($data['password'] ?? '') . "\tCard=" . ($data['card'] ?? 0) . "\tGrp=1\tTZ=0000000100000000",
        ]);

        activity()->withProperties([
            'pin' => $data['pin'],
            'privilege' => $privilege,
        ])->log('zkteco device user queued');

        return redirect()->route('device.index')
            ->with('success', "User {$data['pin']} queued — applied on the device's next poll.");
    }

    /**
     * Queue a user delete on the device.
     */
    public function deleteUser(Request $request)
    {
        $data = $request->validate([
            'pin' => 'required|integer|min:1',
            'sn' => 'nullable|string',
        ]);

        DeviceCommand::create([
            'device_sn' => $data['sn'] ?? null,
            'command' => "DATA DELETE USERINFO PIN={$data['pin']}",
        ]);

        activity()->withProperties(['pin' => $data['pin']])
            ->log('zkteco device user delete queued');

        return redirect()->route('device.index')
            ->with('success', "Delete for user {$data['pin']} queued.");
    }
}
