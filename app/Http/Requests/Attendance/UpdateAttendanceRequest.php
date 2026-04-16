<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
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
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date|before_or_equal:today',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'status' => 'required|string|in:present,absent,late,leave,holiday,half_day',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'The employee field is required.',
            'employee_id.exists' => 'The selected employee does not exist.',
            'date.required' => 'The date field is required.',
            'date.date' => 'The date must be a valid date.',
            'date.before_or_equal' => 'The date cannot be in the future.',
            'check_in.date_format' => 'The check-in time must be in HH:MM format.',
            'check_out.date_format' => 'The check-out time must be in HH:MM format.',
            'check_out.after' => 'The check-out time must be after the check-in time.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The selected status is invalid.',
            'notes.max' => 'The notes may not be greater than 500 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $checkIn = $this->input('check_in');
            $checkOut = $this->input('check_out');
            $status = $this->input('status');
            
            // If status is present, check_in should be provided
            if ($status === 'present' && !$checkIn) {
                $validator->errors()->add('check_in', 'Check-in time is required for present status.');
            }
            
            // If both check_in and check_out are provided, validate time logic
            if ($checkIn && $checkOut) {
                $checkInTime = \Carbon\Carbon::parse($checkIn);
                $checkOutTime = \Carbon\Carbon::parse($checkOut);
                
                if ($checkOutTime->lte($checkInTime)) {
                    $validator->errors()->add('check_out', 'Check-out time must be after check-in time.');
                }
                
                // Check if work hours exceed 24 hours
                $workHours = $checkOutTime->diffInHours($checkInTime);
                if ($workHours > 24) {
                    $validator->errors()->add('check_out', 'Work hours cannot exceed 24 hours.');
                }
            }
        });
    }
}
