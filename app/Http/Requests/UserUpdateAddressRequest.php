<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateAddressRequest extends FormRequest
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
            'id' => "required|numeric",

            'phone' => 'nullable|string',

            'address_line_1' => 'required|string',
            'address_line_2' => 'nullable',
            'country' => 'required|string',
            'city' => 'required|string',
            'postcode' => 'nullable|string',
            'lat' => 'nullable|string',
            'long' => 'nullable|string',


        ];
    }


}
