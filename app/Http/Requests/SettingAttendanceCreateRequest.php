<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingAttendanceCreateRequest extends FormRequest
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
            'punch_in_time_tolerance' => 'nullable|numeric|min:0',
            'work_availability_definition' => 'nullable|numeric|min:0',
            'punch_in_out_alert' => 'nullable|boolean',
            'punch_in_out_interval' => 'nullable|numeric|min:0',
            'alert_area' => 'nullable|array',
            'alert_area.*' => 'string',
            'auto_approval' => 'nullable|boolean',
            'special_users' => 'present|array',
            'special_users.*' => 'numeric',
            'special_roles' => 'present|array',
            'special_roles.*' => 'numeric',
        ];
    }

    public function messages()
    {
        return [
            'punch_in_out_alert.boolean' => 'The :attribute field must be a boolean.',
            'alert_area.array' => 'The :attribute field must be an array.',
            'alert_area.*.string' => 'Each item in :attribute must be a string.',
            'auto_approval.boolean' => 'The :attribute field must be a boolean.',
        ];
    }
    
}
