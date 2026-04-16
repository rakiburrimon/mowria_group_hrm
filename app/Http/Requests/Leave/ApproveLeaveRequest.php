<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequest extends FormRequest
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
            'status' => 'required|string|in:approved,rejected',
            'level' => 'required|integer|in:1,2,3',
            'comments' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be either approved or rejected.',
            'level.required' => 'The approval level field is required.',
            'level.in' => 'The approval level must be 1 (Manager), 2 (HR), or 3 (Director).',
            'comments.max' => 'The comments may not be greater than 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $status = $this->input('status');
            $comments = $this->input('comments');
            
            if ($status === 'rejected' && empty($comments)) {
                $validator->errors()->add('comments', 'Comments are required when rejecting a leave request.');
            }
        });
    }
}
