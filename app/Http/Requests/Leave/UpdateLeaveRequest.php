<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequest extends FormRequest
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
            'type' => 'required|string|in:annual,sick,personal,maternity,paternity',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|numeric|min:0.5|max:365',
            'reason' => 'required|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'The leave type field is required.',
            'type.in' => 'The selected leave type is invalid.',
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.after_or_equal' => 'The start date must be today or a future date.',
            'end_date.required' => 'The end date field is required.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'days.required' => 'The number of days field is required.',
            'days.numeric' => 'The number of days must be a number.',
            'days.min' => 'The number of days must be at least 0.5.',
            'days.max' => 'The number of days cannot exceed 365.',
            'reason.required' => 'The reason field is required.',
            'reason.max' => 'The reason may not be greater than 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');
            
            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
                $calculatedDays = $start->diffInDays($end) + 1;
                $requestedDays = $this->input('days');
                
                if ($requestedDays != $calculatedDays) {
                    $validator->errors()->add('days', 'Number of days does not match the date range selected.');
                }
            }
        });
    }
}
