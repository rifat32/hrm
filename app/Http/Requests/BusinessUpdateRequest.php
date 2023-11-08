<?php

namespace App\Http\Requests;

use App\Rules\SomeTimes;
use Illuminate\Foundation\Http\FormRequest;

class BusinessUpdateRequest extends FormRequest
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
            'user.id' => 'required|numeric|exists:users,id',
            'user.first_Name' => 'required|string|max:255',
            'user.last_Name' => 'required|string|max:255',
            // 'user.email' => 'required|string|email|indisposable|max:255',
            // 'user.email' => 'required|string|email|max:255',
            'user.email' => 'required|string|email|unique:users,email,' . $this->user["id"] . ',id',

            'user.password' => 'nullable|confirmed|string|min:6',
            'user.phone' => 'nullable|string',
            'user.image' => 'nullable',
            // 'user.address_line_1' => 'nullable|string',
            // 'user.address_line_2' => 'nullable|string',
            // 'user.country' => 'nullable|string',
            // 'user.city' => 'nullable|string',
            // 'user.postcode' => 'nullable|string',
            // 'user.lat' => 'nullable|string',
            // 'user.long' => 'nullable|string',

            'business.id' => 'required|numeric|required|exists:businesses,id',
            'business.name' => 'required|string|max:255',
            'business.about' => 'nullable|string',
            'business.web_page' => 'nullable|string',
            'business.phone' => 'nullable|string',
            // 'business.email' => 'required|string|email|indisposable|max:255',
            'business.email' => 'nullable|string|unique:businesses,email,' . $this->business["id"] . ',id',
            'business.additional_information' => 'nullable|string',


            'business.lat' => 'required|string',
            'business.long' => 'required|string',
            'business.country' => 'required|string',
            'business.city' => 'required|string',
            'business.postcode' => 'nullable|string',
            'business.address_line_1' => 'required|string',
            'business.address_line_2' => 'nullable|string',


            'business.logo' => 'nullable|string',
            'business.image' => 'nullable|string',

            'business.images' => 'nullable|array',
            'business.images.*' => 'nullable|string',



            'business.currency' => 'required|string',






        ];


    }

    public function messages()
{
    return [
        'user.id.required' => 'The user ID field is required.',
        'user.id.numeric' => 'The user ID must be a numeric value.',
        'user.id.exists' => 'The selected user ID is invalid.',

        'user.first_Name.required' => 'The first name field is required.',
        'user.first_Name.string' => 'The first name field must be a string.',
        'user.first_Name.max' => 'The first name field may not be greater than :max characters.',

        'user.last_Name.required' => 'The last name field is required.',
        'user.last_Name.string' => 'The last name field must be a string.',
        'user.last_Name.max' => 'The last name field may not be greater than :max characters.',

        'user.email.required' => 'The email field is required.',
        'user.email.email' => 'The email must be a valid email address.',
        'user.email.string' => 'The email field must be a string.',
        'user.email.unique' => 'The email has already been taken.',
        'user.email.exists' => 'The selected email is invalid.',

        'user.password.confirmed' => 'The password confirmation does not match.',
        'user.password.string' => 'The password field must be a string.',
        'user.password.min' => 'The password must be at least :min characters.',

        // 'user.phone.required' => 'The phone field is required.',
        'user.phone.string' => 'The phone field must be a string.',

        'user.image.nullable' => 'The image field must be nullable.',

        'business.id.required' => 'The business ID field is required.',
        'business.id.numeric' => 'The business ID must be a numeric value.',
        'business.id.exists' => 'The selected business ID is invalid.',

        'business.name.required' => 'The name field is required.',
        'business.name.string' => 'The name field must be a string.',
        'business.name.max' => 'The name field may not be greater than :max characters.',

        'business.about.string' => 'The about field must be a string.',
        'business.web_page.string' => 'The web page field must be a string.',
        'business.phone.string' => 'The phone field must be a string.',
        // 'business.email.required' => 'The email field is required.',
        'business.email.email' => 'The email must be a valid email address.',
        'business.email.string' => 'The email field must be a string.',
        'business.email.unique' => 'The email has already been taken.',
        'business.email.exists' => 'The selected email is invalid.',
        'business.additional_information.string' => 'The additional information field must be a string.',

        'business.lat.required' => 'The latitude field is required.',
        'business.lat.string' => 'The latitude field must be a string.',

        'business.long.required' => 'The longitude field is required.',
        'business.long.string' => 'The longitude field must be a string.',

        'business.country.required' => 'The country field is required.',
        'business.country.string' => 'The country field must be a string.',

        'business.city.required' => 'The city field is required.',
        'business.city.string' => 'The city field must be a string.',

        'business.currency.required' => 'The currency field is required.',
        'business.currency.string' => 'The currency must be a string.',

        'business.postcode.string' => 'The postcode field must be a string.',

        'business.address_line_1.required' => 'The address line 1 field is required.',
        'business.address_line_1.string' => 'The address line 1 field must be a string.',

        'business.address_line_2.string' => 'The address line 2 field must be a string.',

        'business.logo.string' => 'The logo field must be a string.',
        'business.image.string' => 'The image field must be a string.',

        'business.images.array' => 'The images field must be an array.',
        'business.images.*.string' => 'Each image in the images field must be a string.',






    ];
}

}
