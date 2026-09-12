<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seed default application settings.
 *
 * Settings are key-value pairs grouped by area. Each entry has a label
 * and input type used by the settings form.
 */
class SettingSeeder extends Seeder
{
    private const SETTINGS = [
        // Company
        ['key' => 'company_name',    'value' => 'Mowria Group',      'group' => 'company', 'label' => 'Company Name',    'type' => 'string'],
        ['key' => 'company_email',   'value' => 'info@mowria.com',   'group' => 'company', 'label' => 'Company Email',   'type' => 'string'],
        ['key' => 'company_phone',   'value' => '',                  'group' => 'company', 'label' => 'Company Phone',   'type' => 'string'],
        ['key' => 'company_address', 'value' => '',                  'group' => 'company', 'label' => 'Company Address', 'type' => 'string'],

        // Attendance
        ['key' => 'work_start_time',    'value' => '09:00', 'group' => 'attendance', 'label' => 'Work Start Time',      'type' => 'time'],
        ['key' => 'work_end_time',      'value' => '17:00', 'group' => 'attendance', 'label' => 'Work End Time',        'type' => 'time'],
        ['key' => 'work_hours_per_day', 'value' => '8',     'group' => 'attendance', 'label' => 'Work Hours per Day',   'type' => 'number'],
        ['key' => 'late_grace_minutes', 'value' => '15',    'group' => 'attendance', 'label' => 'Late Grace (minutes)', 'type' => 'number'],

        // Leave
        ['key' => 'annual_leave_days',  'value' => '21',    'group' => 'leave', 'label' => 'Annual Leave Days', 'type' => 'number'],
        ['key' => 'sick_leave_days',    'value' => '10',    'group' => 'leave', 'label' => 'Sick Leave Days',   'type' => 'number'],

        // General
        ['key' => 'timezone',    'value' => 'Asia/Dhaka', 'group' => 'general', 'label' => 'Timezone',    'type' => 'select', 'options' => 'UTC,Asia/Kabul,Asia/Dhaka,Asia/Karachi,Asia/Kolkata,Asia/Dubai,Europe/London,Europe/Berlin,America/New_York,America/Chicago,America/Los_Angeles,Asia/Tokyo,Australia/Sydney'],
        ['key' => 'date_format', 'value' => 'Y-m-d',      'group' => 'general', 'label' => 'Date Format', 'type' => 'string'],

        // System preferences
        ['key' => 'records_per_page',          'value' => '10',  'group' => 'system', 'label' => 'Records per Page',          'type' => 'number'],
        ['key' => 'default_currency',          'value' => 'AFN', 'group' => 'system', 'label' => 'Default Currency',          'type' => 'select', 'options' => 'AFN,BDT,USD,EUR,INR,PKR'],
        ['key' => 'session_lifetime_minutes',  'value' => '120', 'group' => 'system', 'label' => 'Session Lifetime (minutes)','type' => 'number'],
        ['key' => 'enable_email_notifications','value' => '1',   'group' => 'system', 'label' => 'Enable Email Notifications','type' => 'boolean'],
        ['key' => 'maintenance_mode',          'value' => '0',   'group' => 'system', 'label' => 'Maintenance Mode',          'type' => 'boolean'],

        // Notifications / toaster
        ['key' => 'toast_position',   'value' => 'top-right', 'group' => 'notifications', 'label' => 'Toast Position',     'type' => 'select', 'options' => 'top-right,top-left,top-center,bottom-right,bottom-left'],
        ['key' => 'toast_delay',      'value' => '4000',      'group' => 'notifications', 'label' => 'Toast Delay (ms)',   'type' => 'number'],
        ['key' => 'toast_autohide',   'value' => '1',         'group' => 'notifications', 'label' => 'Auto-hide Toasts',   'type' => 'boolean'],
        ['key' => 'toast_show_icons', 'value' => '1',         'group' => 'notifications', 'label' => 'Show Toast Icons',   'type' => 'boolean'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding settings...');

        foreach (self::SETTINGS as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        $this->command->info(count(self::SETTINGS) . ' settings seeded.');
    }
}
