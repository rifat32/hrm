<?php

namespace App\Http\Requests;

use App\Models\BusinessTime;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UserCreateV2Request extends BaseFormRequest
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

        $all_manager_department_ids = [];
        $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
        foreach ($manager_departments as $manager_department) {
            $all_manager_department_ids[] = $manager_department->id;
            $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
        }


        return [
        'first_Name' => 'required|string|max:255',
        'middle_Name' => 'nullable|string|max:255',

        'last_Name' => 'required|string|max:255',

        'user_id' => [
            "required",
            'string',
            function ($attribute, $value, $fail) {
                $user_id_exists =  User::where([
                    'user_id'=> $value,
                    "created_by" => auth()->user()->id
                 ]
                 )->exists();
                 if ($user_id_exists){
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
                $role  = Role::where(["name" => $value])->first();


                if (!$role){
                         // $fail($attribute . " is invalid.")
                         $fail("Role does not exists.");
                         return;

                }

                if(!empty(auth()->user()->business_id)) {
                    if (empty($role->business_id)){
                        // $fail($attribute . " is invalid.")
                      $fail("You don't have this role");
                      return;

                  }
                    if ($role->business_id != auth()->user()->business_id){
                          // $fail($attribute . " is invalid.")
                        $fail("You don't have this role");
                        return;

                    }
                } else {
                    if (!empty($role->business_id)){
                        // $fail($attribute . " is invalid.")
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


                     $business_times =    BusinessTime::where([
                "is_weekend" => 1,
                "business_id" => auth()->user()->business_id,
            ])->get();


                    $exists = WorkShift::where('id', $value)
                    ->where([
                        "work_shifts.business_id" => auth()->user()->business_id
                    ])
                    ->orWhere(function($query) use($business_times) {
                        $query->where([
                            "is_active" => 1,
                            "business_id" => NULL,
                            "is_default" => 1
                        ])
                    //     ->whereHas('details', function($query) use($business_times) {

                    //     foreach($business_times as $business_time) {
                    //         $query->where([
                    //             "day" => $business_time->day,
                    //         ]);
                    //         if($business_time["is_weekend"]) {
                    //             $query->where([
                    //                 "is_weekend" => 1,
                    //             ]);
                    //         } else {
                    //             $query->where(function($query) use($business_time) {
                    //                 $query->whereTime("start_at", ">=", $business_time->start_at);
                    //                 $query->orWhereTime("end_at", "<=", $business_time->end_at);
                    //             });
                    //         }

                    //     }
                    // })
                    ;

                    })

                    ->exists();

                if (!$exists) {
                    $fail($attribute . " is invalid.");
                }
                }

            },
        ],

        'departments' => 'required|array|size:1',
        'departments.*' =>  [
            'numeric',
            function ($attribute, $value, $fail) use($all_manager_department_ids) {

                $department = Department::where('id', $value)
                    ->where('departments.business_id', '=', auth()->user()->business_id)
                    ->first();

                    if (!$department) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                    if(!in_array($department->id,$all_manager_department_ids)){
                        $fail($attribute . " is invalid. You don't have access to this department.");
                        return;
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
                    $fail($attribute . " is invalid.");
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
                $fail($attribute . " is invalid.");
            }


            },
        ],

        'recruitment_processes' => "present|array",
        'recruitment_processes.*.recruitment_process_id' => [
            "required",
            'numeric',
            function ($attribute, $value, $fail) {

                $created_by  = NULL;
                if(auth()->user()->business) {
                    $created_by = auth()->user()->business->created_by;
                }

                $exists = RecruitmentProcess::where("recruitment_processes.id",$value)
                ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                    if (auth()->user()->hasRole('superadmin')) {
                        return $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 1)
                            ->where('recruitment_processes.is_active', 1);

                    } else {
                        return $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 1)
                            ->where('recruitment_processes.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) {
                                $q->whereIn("disabled_recruitment_processes.created_by", [auth()->user()->id]);
                            })

                            ->orWhere(function ($query) use($value)  {
                                $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', NULL)
                                    ->where('recruitment_processes.is_default', 0)
                                    ->where('recruitment_processes.created_by', auth()->user()->id)
                                    ->where('recruitment_processes.is_active', 1);


                            });
                    }
                })
                    ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                        return $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 1)
                            ->where('recruitment_processes.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) use($created_by) {
                                $q->whereIn("disabled_recruitment_processes.created_by", [$created_by]);
                            })
                            ->whereDoesntHave("disabled", function($q)  {
                                $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                            })

                            ->orWhere(function ($query) use( $created_by, $value){
                                $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', NULL)
                                    ->where('recruitment_processes.is_default', 0)
                                    ->where('recruitment_processes.created_by', $created_by)
                                    ->where('recruitment_processes.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                                    });
                            })
                            ->orWhere(function ($query) use($value)  {
                                $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', auth()->user()->business_id)
                                    ->where('recruitment_processes.is_default', 0)
                                    ->where('recruitment_processes.is_active', 1);

                            });
                    })
                ->exists();

            if (!$exists) {
                $fail($attribute . " is invalid.");
            }


            },
        ],
        'recruitment_processes.*.description' => "nullable|string",
        'recruitment_processes.*.attachments' => "present|array",

        'work_location_id' => [
            "required",
            'numeric',
            function ($attribute, $value, $fail) {

                $created_by  = NULL;
                if(auth()->user()->business) {
                    $created_by = auth()->user()->business->created_by;
                }

                $exists = WorkLocation::where("work_locations.id",$value)
                ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                    if (auth()->user()->hasRole('superadmin')) {
                        return $query->where('work_locations.business_id', NULL)
                            ->where('work_locations.is_default', 1)
                            ->where('work_locations.is_active', 1);

                    } else {
                        return $query->where('work_locations.business_id', NULL)
                            ->where('work_locations.is_default', 1)
                            ->where('work_locations.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) {
                                $q->whereIn("disabled_work_locations.created_by", [auth()->user()->id]);
                            })

                            ->orWhere(function ($query) use($value)  {
                                $query->where("work_locations.id",$value)->where('work_locations.business_id', NULL)
                                    ->where('work_locations.is_default', 0)
                                    ->where('work_locations.created_by', auth()->user()->id)
                                    ->where('work_locations.is_active', 1);


                            });
                    }
                })
                    ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                        return $query->where('work_locations.business_id', NULL)
                            ->where('work_locations.is_default', 1)
                            ->where('work_locations.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) use($created_by) {
                                $q->whereIn("disabled_work_locations.created_by", [$created_by]);
                            })
                            ->whereDoesntHave("disabled", function($q)  {
                                $q->whereIn("disabled_work_locations.business_id",[auth()->user()->business_id]);
                            })

                            ->orWhere(function ($query) use( $created_by, $value){
                                $query->where("work_locations.id",$value)->where('work_locations.business_id', NULL)
                                    ->where('work_locations.is_default', 0)
                                    ->where('work_locations.created_by', $created_by)
                                    ->where('work_locations.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_work_locations.business_id",[auth()->user()->business_id]);
                                    });
                            })
                            ->orWhere(function ($query) use($value)  {
                                $query->where("work_locations.id",$value)->where('work_locations.business_id', auth()->user()->business_id)
                                    // ->where('work_locations.is_default', 0)
                                    ->where('work_locations.is_active', 1);

                            });
                    })
                ->exists();

            if (!$exists) {
                $fail($attribute . " is invalid.");
            }

            },
        ],




        'joining_date' => [
            "required",
            'date',
            function ($attribute, $value, $fail) {

               $joining_date = Carbon::parse($value);
               $start_date = Carbon::parse(auth()->user()->business->start_date);

               if ($joining_date->lessThan($start_date)) {
                   $fail("The $attribute must not be before the start date of the business.");
               }

            },
        ],




        'salary_per_annum' => "required|numeric",
        'weekly_contractual_hours' => 'required|numeric',
        "minimum_working_days_per_week" => 'required|numeric|max:7',
        "overtime_rate" => 'required|numeric',
        'emergency_contact_details' => "present|array",



        "immigration_status" => "required|in:british_citizen,ilr,immigrant,sponsored",

        'is_sponsorship_offered' => "nullable|boolean",






        'date' => 'nullable|required_if:leave_duration,single_day,half_day,hours|date',

        "is_active_visa_details" => 'required|boolean',
        "is_active_right_to_works" => "required|boolean",

        "sponsorship_details.date_assigned" => 'nullable|required_if:immigration_status,sponsored|date',
        "sponsorship_details.expiry_date" => 'nullable|required_if:immigration_status,sponsored|date',
        // "sponsorship_details.status" => 'nullable|required_if:immigration_status,sponsored|in:pending,approved,denied,visa_granted',
        "sponsorship_details.note" => 'nullable|required_if:immigration_status,sponsored|string',
        "sponsorship_details.certificate_number" => 'nullable|required_if:immigration_status,sponsored|string',
        "sponsorship_details.current_certificate_status" => 'nullable|required_if:immigration_status,sponsored|in:unassigned,assigned,visa_applied,visa_rejected,visa_grantes,withdrawal',
        "sponsorship_details.is_sponsorship_withdrawn" => 'nullable|required_if:immigration_status,sponsored|boolean',





        'passport_details.passport_number' => 'nullable|required_if:immigration_status,sponsored,immigrant|string',
        'passport_details.passport_issue_date' => 'nullable|required_if:immigration_status,sponsored,immigrant|date',
        'passport_details.passport_expiry_date' => 'nullable|required_if:immigration_status,sponsored,immigrant|date',
        'passport_details.place_of_issue' => 'nullable|required_if:immigration_status,sponsored,immigrant|string',




        'visa_details.BRP_number' => 'nullable|required_if:is_active_visa_details,1|string',
        'visa_details.visa_issue_date' => 'nullable|required_if:is_active_visa_details,1|date',
        'visa_details.visa_expiry_date' => 'nullable|required_if:is_active_visa_details,1|date',
        'visa_details.place_of_issue' => 'nullable|required_if:is_active_visa_details,1|string',
        'visa_details.visa_docs' => 'nullable|required_if:is_active_visa_details,1|array',
        'visa_details.visa_docs.*.file_name' => 'nullable|required_if:is_active_visa_details,1|string',
        'visa_details.visa_docs.*.description' => 'nullable|string',

       

    ];

    }
    public function messages()
    {
        return [

            'immigration_status.in' => 'Invalid value for status. Valid values are: british_citizen, ilr, immigrant, sponsored.',
            // 'sponsorship_details.status.in' => 'Invalid value for status. Valid values are: pending,approved,denied,visa_granted.',
            'sponsorship_details.current_certificate_status.in' => 'Invalid value for status. Valid values are: unassigned,assigned,visa_applied,visa_rejected,visa_grantes,withdrawal.',

            // ... other custom messages
        ];
    }

}
