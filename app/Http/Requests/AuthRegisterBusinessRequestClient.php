<?php

namespace App\Http\Requests;

use App\Rules\DayValidation;
use App\Rules\SomeTimes;
use App\Rules\TimeOrderRule;
use App\Rules\TimeValidation;
use Illuminate\Foundation\Http\FormRequest;

class AuthRegisterBusinessRequestClient extends FormRequest
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
            'user.first_Name' => 'required|string|max:255',
            'user.last_Name' => 'required|string|max:255',
            // 'user.email' => 'required|string|email|indisposable|max:255|unique:users,email',
            'user.email' => 'required|string|email|max:255|unique:users,email',
            'user.password' => 'required|confirmed|string|min:6',
            'user.phone' => 'nullable|string',
            'user.image' => 'nullable|string',

            // 'user.address_line_1' => 'nullable|string',
            // 'user.address_line_2' => 'nullable|string',
            // 'user.country' => 'nullable|string',
            // 'user.city' => 'nullable|string',
            // 'user.postcode' => 'nullable|string',

            // 'user.lat' => 'nullable|string',
            // 'user.long' => 'nullable|string',

            'business.name' => 'required|string|max:255',
            'business.about' => 'nullable|string',
            'business.web_page' => 'nullable|string',
            'business.phone' => 'nullable|string',
            // 'business.email' => 'required|string|email|indisposable|max:255|unique:businesses,email',
            'business.email' => 'nullable|string|email|max:255|unique:businesses,email',
            'business.additional_information' => 'nullable|string',

            'business.lat' => 'required|string',
            'business.long' => 'required|string',
            'business.country' => 'required|string',
            'business.city' => 'required|string',

            'business.currency' => 'required|string',

            'business.postcode' => 'required|string',
            'business.address_line_1' => 'required|string',
            'business.address_line_2' => 'nullable|string',


            'business.logo' => 'nullable|string',

            'business.image' => 'nullable|string',

            'business.images' => 'nullable|array',
            'business.images.*' => 'nullable|string',













        ];


    }
    public function messages()
    {
        return [
            'user.first_Name.required' => 'The first name field is required.',
            'user.last_Name.required' => 'The last name field is required.',
            'user.email.required' => 'The email field is required.',
            'user.email.email' => 'The email must be a valid email address.',
            'user.email.email' => 'The email must be a valid email address.',
            'user.password.required' => 'The password field is required.',
            'user.password.confirmed' => 'The password confirmation does not match.',
            // 'user.phone.required' => 'The phone field is required.',
            'user.image.string' => 'The image must be a string.',
            'user.email.unique' => 'The email has already been taken.',

            'business.name.required' => 'The name field is required.',
            'business.about.string' => 'The about field must be a string.',
            'business.web_page.string' => 'The web page field must be a string.',
            'business.phone.string' => 'The phone field must be a string.',
            // 'business.email.required' => 'The email field is required.',
            'business.email.email' => 'The email must be a valid email address.',
            'business.email.unique' => 'The email has already been taken.',
            'business.additional_information.string' => 'The additional information field must be a string.',
            'business.lat.required' => 'The latitude field is required.',
            'business.long.required' => 'The longitude field is required.',
            'business.country.required' => 'The country field is required.',
            'business.city.required' => 'The city field is required.',
            'business.currency.required' => 'The currency field is required.',
            'business.currency.string' => 'The currency field must be a string.',
            'business.postcode.required' => 'The postcode field is required.',
            'business.address_line_1.required' => 'The address line 1 field is required.',
            'business.address_line_2.string' => 'The address line 2 field must be a string.',
            'business.logo.string' => 'The logo field must be a string.',
            'business.image.string' => 'The image field must be a string.',
            'business.images.array' => 'The images field must be an array.',
            'business.images.*.string' => 'Each image in the images field must be a string.',
        







        ];
    }





}
