<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UploadProfileImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'profile_image.required' => 'The profile image field is required.',
            'profile_image.image' => 'The profile image must be an image.',
            'profile_image.mimes' => 'The profile image must be a file of type: jpg, jpeg, png, gif.',
            'profile_image.max' => 'The profile image may not be greater than 2MB.',
        ];
    }
}
