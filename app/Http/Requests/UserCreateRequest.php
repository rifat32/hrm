<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCreateRequest extends FormRequest
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
        'first_Name' => 'required|string|max:255',
        'last_Name' => 'required|string|max:255',
        'employee_id' => 'nullable|string',

        // 'email' => 'required|string|email|indisposable|max:255|unique:users',
        'email' => 'required|string|email|max:255|unique:users',

        'password' => 'required|confirmed|string|min:6',
        'phone' => 'required|string',
        'image' => 'nullable',
        'address_line_1' => 'required|string',
        'address_line_2' => 'nullable',
        'country' => 'required|string',
        'city' => 'required|string',
        'postcode' => 'nullable|string',
        'lat' => 'required|string',
        'long' => 'required|string',
        'role' => 'required|string',
        'departments' => 'nullable|array',
        'departments.*' => 'numeric',

        'gender' => 'required|string|in:male,female,other',
        'is_in_employee' => "nullable|boolean",
        'designation_id' => "nullable|numeric",
        'employment_status_id' => "nullable|numeric",
        'joining_date' => "nullable|date",
        'salary' => "nullable|string",


    ];

    }
}
