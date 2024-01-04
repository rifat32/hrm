<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateV2Request extends FormRequest
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
            'first_Name' => 'required|string|max:255',
            'middle_Name' => 'nullable|string|max:255',

            'last_Name' => 'required|string|max:255',

            'employee_id' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {
                    $employee_id_exists =  User::
                    whereNotIn("id",[$this->id])
                    ->where([
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
            'email' => 'required|string|unique:users,email,' . $this->id . ',id',

            'password' => 'nullable|string|min:6',
            'phone' => 'nullable|string',
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
                    $role  = Role::where(["name" => $value])->first();


                    if (!$role){
                             // $fail("$attribute is invalid.")
                             $fail("Role does not exists.");
                             return;

                    }

                    if(!empty(auth()->user()->business_id)) {
                        if (empty($role->business_id)){
                            // $fail("$attribute is invalid.")
                          $fail("You don't have this role");
                          return;

                      }
                        if ($role->business_id != auth()->user()->business_id){
                              // $fail("$attribute is invalid.")
                            $fail("You don't have this role");
                            return;

                        }
                    } else {
                        if (!empty($role->business_id)){
                            // $fail("$attribute is invalid.")
                          $fail("You don't have this role");
                          return;

                      }
                    }


                },
            ],



        'work_shift_id' => [
            "nullable",
            'numeric',
            function ($attribute, $value, $fail) {
                if(!empty($value)){
                    $exists = WorkShift::where('id', $value)
                    ->where('work_shifts.business_id', '=', auth()->user()->business_id)
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }
                }

            },
        ],

            'departments' => 'required|array',
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

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = Designation::where("designations.id",$value)
                        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                            if (auth()->user()->hasRole('superadmin')) {
                                return $query->where('designations.business_id', NULL)
                                    ->where('designations.is_default', 1)
                                    ->where('designations.is_active', 1);

                            } else {
                                return $query->where('designations.business_id', NULL)
                                    ->where('designations.is_default', 1)
                                    ->where('designations.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_designations.created_by", [auth()->user()->id]);
                                    })

                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("designations.id",$value)->where('designations.business_id', NULL)
                                            ->where('designations.is_default', 0)
                                            ->where('designations.created_by', auth()->user()->id)
                                            ->where('designations.is_active', 1);


                                    });
                            }
                        })
                            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                                return $query->where('designations.business_id', NULL)
                                    ->where('designations.is_default', 1)
                                    ->where('designations.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                                        $q->whereIn("disabled_designations.created_by", [$created_by]);
                                    })
                                    ->whereDoesntHave("disabled", function($q)  {
                                        $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
                                    })

                                    ->orWhere(function ($query) use( $created_by, $value){
                                        $query->where("designations.id",$value)->where('designations.business_id', NULL)
                                            ->where('designations.is_default', 0)
                                            ->where('designations.created_by', $created_by)
                                            ->where('designations.is_active', 1)
                                            ->whereDoesntHave("disabled", function($q) {
                                                $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
                                            });
                                    })
                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("designations.id",$value)->where('designations.business_id', auth()->user()->business_id)
                                            ->where('designations.is_default', 0)
                                            ->where('designations.is_active', 1);

                                    });
                            })
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

                    $created_by  = NULL;
                    if(auth()->user()->business) {
                        $created_by = auth()->user()->business->created_by;
                    }

                    $exists = EmploymentStatus::where("employment_statuses.id",$value)
                    ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                        if (auth()->user()->hasRole('superadmin')) {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1);

                        } else {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) {
                                    $q->whereIn("disabled_employment_statuses.created_by", [auth()->user()->id]);
                                })

                                ->orWhere(function ($query) use($value)  {
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.created_by', auth()->user()->id)
                                        ->where('employment_statuses.is_active', 1);


                                });
                        }
                    })
                        ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) use($created_by) {
                                    $q->whereIn("disabled_employment_statuses.created_by", [$created_by]);
                                })
                                ->whereDoesntHave("disabled", function($q)  {
                                    $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                })

                                ->orWhere(function ($query) use( $created_by, $value){
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.created_by', $created_by)
                                        ->where('employment_statuses.is_active', 1)
                                        ->whereDoesntHave("disabled", function($q) {
                                            $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                        });
                                })
                                ->orWhere(function ($query) use($value)  {
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', auth()->user()->business_id)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.is_active', 1);

                                });
                        })
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








            "sponsorship_details.date_assigned" => 'nullable|required_if:immigration_status,sponsored|date',
            "sponsorship_details.expiry_date" => 'nullable|required_if:immigration_status,sponsored|date',
            "sponsorship_details.status" => 'nullable|required_if:immigration_status,sponsored|in:pending,approved,denied,visa_granted',
            "sponsorship_details.note" => 'nullable|required_if:immigration_status,sponsored|string',
            "sponsorship_details.certificate_number" => 'nullable|required_if:immigration_status,sponsored|string',
            "sponsorship_details.current_certificate_status" => 'nullable|required_if:immigration_status,sponsored|in:pending,approved,denied',
            "sponsorship_details.is_sponsorship_withdrawn" => 'nullable|required_if:immigration_status,sponsored|boolean',





            'passport_details.passport_number' => 'nullable|required_if:immigration_status,sponsored,immigrant|string',
            'passport_details.passport_issue_date' => 'nullable|required_if:immigration_status,sponsored,immigrant|date',
            'passport_details.passport_expiry_date' => 'nullable|required_if:immigration_status,sponsored,immigrant|date',
            'passport_details.place_of_issue' => 'nullable|required_if:immigration_status,sponsored,immigrant|string',




            'visa_details.BRP_number' => 'nullable|required_if:immigration_status,sponsored,immigrant|string',
            'visa_details.visa_issue_date' => 'nullable|required_if:immigration_status,sponsored,immigrant|date',
            'visa_details.visa_expiry_date' => 'nullable|required_if:immigration_status,sponsored,immigrant|date',
            'visa_details.place_of_issue' => 'nullable|required_if:immigration_status,sponsored,immigrant|string',
            'visa_details.visa_docs' => 'nullable|required_if:immigration_status,sponsored,immigrant|array',
            'visa_details.visa_docs.*.file_name' => 'nullable|required_if:immigration_status,sponsored,immigrant|string',
            'visa_details.visa_docs.*.description' => 'nullable|string',

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
