<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Traits\HandlesServiceExceptions;
use App\Traits\LogsActions;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use HandlesServiceExceptions;
    use LogsActions;

    /**
     * Display the settings form, grouped by section.
     */
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('id')->get()->groupBy('group');

        return view('settings.index', compact('settings'));
    }

    /**
     * Update settings from the submitted form.
     */
    public function update(Request $request)
    {
        return $this->handleService(function () use ($request) {
            // Only accept keys that exist as settings
            $keys = Setting::pluck('key')->all();
            $data = $request->only($keys);

            foreach ($data as $key => $value) {
                $setting = Setting::where('key', $key)->first();

                // Log the change with the setting as the subject, then save
                $this->logAction('setting ' . $key . ' updated', $setting, [
                    'key' => $key,
                    'old_value' => $setting?->value,
                    'new_value' => $value,
                ]);

                Setting::set($key, $value);
            }

            return redirect()
                ->route('settings.index')
                ->with('success', 'Settings updated successfully.');
        }, 'Failed to update settings');
    }
}
