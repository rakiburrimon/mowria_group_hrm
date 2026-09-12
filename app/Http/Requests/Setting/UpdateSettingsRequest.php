<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules are built dynamically from the settings table — each existing
     * key is validated by its declared type.
     */
    public function rules(): array
    {
        $rules = [];

        foreach (Setting::all() as $setting) {
            $rules[$setting->key] = match ($setting->type) {
                'number' => 'nullable|numeric',
                'boolean' => 'nullable|in:0,1',
                'time' => 'nullable|date_format:H:i',
                'select' => 'nullable|string|in:' . ($setting->options ?? ''),
                default => 'nullable|string|max:500',
            };
        }

        return $rules;
    }
}
