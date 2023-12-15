<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserCreateV2Request extends FormRequest
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

        'employee_id' => [
            "required",
            'string',
            function ($attribute, $value, $fail) {
                $employee_id_exists =  User::where([
                    'employee_id'=> $value,
                    "created_by" => auth()->user()->id
                 ]
                 )->exists();
                 if ($employee_id_exists){
                      $fail("The employee id has already been taken.");
                   }


            },
        ],

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


        'work_shift_id' => [
            "nullable",
            'numeric'
        ],

        'departments' => 'present|array',
        'departments.*' =>  ['numeric',
            function ($attribute, $value, $fail) {
                $exists = Department::where('id', $value)
                    ->where('departments.business_id', '=', auth()->user()->business_id)
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }
            },
        ],


        'gender' => 'nullable|string|in:male,female,other',
        'is_in_employee' => "required|boolean",
        'designation_id' => [
            "required",
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
        'employment_status_id' => [
            "required",
            'numeric',
            function ($attribute, $value, $fail) {
                $exists = EmploymentStatus::where('id', $value)
                    ->where('employment_statuses.business_id', '=', auth()->user()->business_id)
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }
            },
        ],

        'joining_date' => "required|date",
        'salary_per_annum' => "required|numeric",
        'emergency_contact_details' => "present|array",



        "immigration_status" => "required|in:british_citizen,ilr,immigrant,sponsored",

        'is_sponsorship_offered' => "nullable|boolean",


        "sponsorship_details" => "nullable|array",

        "sponsorship_details.*.date_assigned" => 'required|date',
        "sponsorship_details.*.expiry_date" => 'required|date',
        "sponsorship_details.*.status" => 'required|in:pending,approved,denied,visa_granted',
        "sponsorship_details.*.note" => 'required|string',
        "sponsorship_details.*.certificate_number" => 'required|string',
        "sponsorship_details.*.certificate_number" => 'required|string',
        "sponsorship_details.*.current_certificate_status" => 'required|in:pending,approved,denied',
        "sponsorship_details.*.is_sponsorship_withdrawn" => 'required|boolean',

        "passport_details" => "nullable|array",
        'passport_details.*.passport_number' => 'required|string',
        'passport_details.*.passport_issue_date' => 'required|date',
        'passport_details.*.passport_expiry_date' => 'required|date',
        'passport_details.*.place_of_issue' => 'required|string',



        "visa_details" => "nullable|array",
        'visa_details.*.BRP_number' => 'required|string',
        'visa_details.*.visa_issue_date' => 'required|date',
        'visa_details.*.visa_expiry_date' => 'required|date',
        'visa_details.*.place_of_issue' => 'required|string',
        'visa_details.*.visa_docs' => 'present|array',
        'visa_details.*.visa_docs.*.file_name' => 'required|string',
        'visa_details.*.visa_docs.*.description' => 'nullable|string',


    ];

    }
    public function messages()
    {
        return [

            'immigration_status.in' => 'Invalid value for status. Valid values are: british_citizen, ilr, immigrant, sponsored.',
            'sponsorship_details.status.in' => 'Invalid value for status. Valid values are: pending,approved,denied,visa_granted.',
            'sponsorship_details.current_certificate_status.in' => 'Invalid value for status. Valid values are: pending,approved,denied.',

            // ... other custom messages
        ];
    }

}
