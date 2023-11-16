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


        $rules = [
            'name' => 'required|string',
            'type' => 'required|string|in:paid,unpaid',
            'amount' => 'required|numeric',
            'is_active' => 'required|boolean',
            'is_earning_enabled' => 'required|boolean',
        ];

        if (!empty(auth()->user()->business_id)) {
            $rules['name'] .= '|unique:setting_leave_types,name,NULL,id,business_id,' . auth()->user()->business_id;
        } else {
            $rules['name'] .= '|unique:setting_leave_types,name,NULL,id,is_default,' . (auth()->user()->hasRole('superadmin') ? 1 : 0);
        }

return $rules;


    }

    public function messages()
    {
        return [
            'type.in' => 'The :attribute field must be either "paid" or "unpaid".',
        ];
    }
}
