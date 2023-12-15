<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
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
        'employee_id' => 'nullable|string',

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
        'role' => 'required|string',

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
        'employment_status_id' => [
            "nullable",
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

        'joining_date' => "nullable|date",
        'salary_per_annum' => "nullable|numeric",
        'emergency_contact_details' => "nullable|array",



    ];

    }
}
