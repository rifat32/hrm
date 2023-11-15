<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingLeaveCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_month' => 'required|integer|min:0|max:11',
            'approval_level' => 'required|string|in:single,multiple', // Adjust the valid values as needed
            'allow_bypass' => 'required|boolean',
            'special_users' => 'present|array',
            'special_users.*' => "numeric",
            'special_roles' => 'present|array',
            'special_roles.*' => "numeric",
            'paid_leave_employment_statuses' => 'present|array',
            'paid_leave_employment_statuses.*' => "numeric",
            'unpaid_leave_employment_statuses' => 'present|array',
            'unpaid_leave_employment_statuses.*' => "numeric",
        ];

    }
}
