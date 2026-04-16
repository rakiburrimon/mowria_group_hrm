<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
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
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string|in:annual,sick,personal,maternity,paternity',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:pending,approved,rejected,cancelled',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'type.in' => 'Leave type must be annual, sick, personal, maternity, or paternity.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'days.max' => 'Leave days cannot exceed 365.',
            'status.in' => 'Status must be pending, approved, rejected, or cancelled.',
        ];
    }
}
