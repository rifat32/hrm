<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\BusinessTime;
use App\Models\Role;
use App\Models\WorkShift;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateDesignationId;
use App\Rules\ValidEmploymentStatus;
use App\Rules\ValidUserId;
use App\Rules\ValidWorkLocationId;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UserRejoinRequest extends FormRequest
{
    use BasicUtil;
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

        $all_manager_department_ids = $this->get_all_departments_of_manager();

        return [
            'id' => [
                "required",
                "numeric",
                new ValidUserId($all_manager_department_ids),
            ],


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


                //          $business_times =    BusinessTime::where([
                //     "is_weekend" => 1,
                //     "business_id" => auth()->user()->business_id,
                // ])->get();


                        $exists = WorkShift::where('id', $value)
                        ->where([
                            "work_shifts.business_id" => auth()->user()->business_id
                        ])
                        ->orWhere(function($query) {
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
                new ValidateDepartment($all_manager_department_ids)
            ],



        'work_location_ids' => [
            "present",
            'array',
        ],

        "work_location_ids.*" =>[
            "numeric",
        new ValidWorkLocationId()],





            'designation_id' => [
                "required",
                'numeric',
                new ValidateDesignationId()
            ],
            'employment_status_id' => [
                "required",
                'numeric',
                new ValidEmploymentStatus()
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
            "overtime_rate" => 'nullable|numeric',
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
