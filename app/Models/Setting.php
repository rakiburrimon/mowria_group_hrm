<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Application key-value setting.
 *
 * Settings are grouped (e.g. "company", "attendance", "leave") and can be
 * read anywhere via Setting::get('key', 'default').
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'label', 'type', 'options'];

    /**
     * Get a setting value by key, with optional default.
     *
     * Only the scalar value is cached — never the model — because the
     * database cache store does not unserialize classes.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    /**
     * Update or create a setting and flush its cached value.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("setting_{$key}");
    }
}
