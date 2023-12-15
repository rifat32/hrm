<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\Role;
use App\Models\WorkShift;
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
        'middle_Name' => 'nullable|string|max:255',

        'last_Name' => 'required|string|max:255',


        // 'email' => 'required|string|email|indisposable|max:255|unique:users',
        'email' => 'required|string|email|max:255|unique:users',

        'password' => 'required|string|min:6',
        'phone' => 'required|string',
        'image' => 'nullable|string',
        'address_line_1' => 'required|string',
        'address_line_2' => 'nullable',
        'country' => 'required|string',
        'city' => 'required|string',
        'postcode' => 'nullable|string',
        'lat' => 'nullable|string',
        'long' => 'nullable|string',
        'role' => [
            "required",
            'string',
            function ($attribute, $value, $fail) {
                $role  = Role::where(["name" => "value"])->first();


                if (!$role){
                         // $fail("$attribute is invalid.")
                         $fail("Role does not exists.");

                }

                if(!empty(auth()->user()->business_id)) {
                    if ($role->business_id != auth()->user()->business_id){
                          // $fail("$attribute is invalid.")
                        $fail("You don't have this role");

                    }
                }


            },
        ],







        'gender' => 'nullable|string|in:male,female,other',
        'is_in_employee' => "nullable|boolean",
        'designation_id' => [
            "nullable",
            'numeric',
            function ($attribute, $value, $fail) {
                $exists = Designation::where('id', $value)
                    ->where('designations.business_id', '=', auth()->user()->business_id)
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }
            },
        ],

        'joining_date' => "nullable|date",
        'salary_per_annum' => "nullable|numeric",



    ];

    }
}
