<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'employee_id' => 'required|string|max:50|unique:employees,employee_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:255',
            'hire_date' => 'required|date',
            'salary' => 'nullable|numeric|min:0',
            'status' => 'required|in:' . implode(',', [
                \App\Models\Employee::STATUS_ACTIVE,
                \App\Models\Employee::STATUS_INACTIVE,
                \App\Models\Employee::STATUS_TERMINATED
            ]),
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'The user field is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'department_id.required' => 'The department field is required.',
            'department_id.exists' => 'The selected department does not exist.',
            'employee_id.required' => 'The employee ID field is required.',
            'employee_id.unique' => 'The employee ID has already been taken.',
            'employee_id.max' => 'The employee ID may not be greater than 50 characters.',
            'first_name.required' => 'The first name field is required.',
            'first_name.max' => 'The first name may not be greater than 255 characters.',
            'last_name.required' => 'The last name field is required.',
            'last_name.max' => 'The last name may not be greater than 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.max' => 'The email may not be greater than 255 characters.',
            'email.unique' => 'The email has already been taken.',
            'phone.max' => 'The phone number may not be greater than 20 characters.',
            'position.required' => 'The position field is required.',
            'position.max' => 'The position may not be greater than 255 characters.',
            'hire_date.required' => 'The hire date field is required.',
            'hire_date.date' => 'The hire date is not a valid date.',
            'salary.numeric' => 'The salary must be a number.',
            'salary.min' => 'The salary must be at least 0.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The selected status is invalid.',
            'profile_image.image' => 'The profile image must be an image.',
            'profile_image.mimes' => 'The profile image must be a file of type: jpg, jpeg, png, gif.',
            'profile_image.max' => 'The profile image may not be greater than 2MB.',
        ];
    }
}
