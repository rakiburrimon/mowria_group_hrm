<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee');
        
        return [
            'user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'required|string|max:50|unique:employees,employee_id,' . $employeeId,
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
}
