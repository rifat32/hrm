<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingPayrunCreateRequest extends FormRequest
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
            'payrun_period' => 'required|in:monthly,weekly',
            'consider_type' => 'required|in:hour,daily_log,none',
            'consider_overtime' => 'required|boolean',
        ];
    }


    public function messages()
    {
        return [
            'payrun_period.required' => 'The :attribute field is required.',
            'payrun_period.in' => 'The :attribute must be either "monthly" or "weekly".',
            'consider_type.required' => 'The :attribute field is required.',
            'consider_type.in' => 'The :attribute must be either "hour", "daily_log", or "none".',
            'consider_overtime.required' => 'The :attribute field is required.',
            'consider_overtime.boolean' => 'The :attribute field must be a boolean.',
        ];
    }



}
