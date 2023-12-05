<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreDetailsRequest extends FormRequest
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
            'employee_id' => 'required|numeric',
            'date_assigned' => 'required|date',
            'expiry_date' => 'required|date',
            'status' => 'required|in:pending,approved,denied,visa_granted',
            'note' => 'nullable|string',
        ];

        if ($this->input('status') === 'visa_granted') {
            $rules['passport.passport_number'] = 'required|string';
            $rules['passport.passport_issue_date'] = 'required|date';
            $rules['passport.passport_expiry_date'] = 'required|date';
            $rules['passport.place_of_issue'] = 'required|string';



            $rules['passport.visa.BRP_number'] = 'required|string';
            $rules['passport.visa.visa_issue_date'] = 'required|date';
            $rules['passport.visa.visa_expiry_date'] = 'required|date';
            $rules['passport.visa.place_of_issue'] = 'required|string';
            $rules['passport.visa.visa_docs'] = 'present|array';
            $rules['passport.visa.visa_docs.*.file_name'] = 'required|string';
            $rules['passport.visa.visa_docs.*.description'] = 'nullable|string';

        }

        return $rules;
    }

    public function messages()
    {
        return [

            'sponsorship.status.in' => 'Invalid value for status. Valid values are: pending,approved,denied,visa_granted.',
            // ... other custom messages
        ];
    }



}
