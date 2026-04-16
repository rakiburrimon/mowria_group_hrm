<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'required|string|max:50|unique:employees,employee_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:255',
            'hire_date' => 'required|date|before_or_equal:today',
            'salary' => 'nullable|numeric|min:0|max:999999.99',
            'status' => 'required|string|in:active,inactive,terminated',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'Selected user does not exist.',
            'employee_id.unique' => 'Employee ID already exists.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
            'status.in' => 'Status must be active, inactive, or terminated.',
        ];
    }
}
