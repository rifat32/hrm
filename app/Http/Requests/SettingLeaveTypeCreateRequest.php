<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingLeaveTypeCreateRequest extends FormRequest
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
            'name' => 'required|string',
            'type' => 'required|string|in:paid,unpaid',
            'amount' => 'required|numeric',
            'is_active' => 'required|boolean',
            'is_earning_enabled' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'type.in' => 'The :attribute field must be either "paid" or "unpaid".',
        ];
    }
}
