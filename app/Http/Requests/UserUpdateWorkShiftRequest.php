<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\BusinessTime;
use App\Models\Department;
use App\Models\Designation;

use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateDesignation;
use App\Rules\ValidateRecruitmentProcess;
use App\Rules\ValidateEmploymentStatus;
use App\Rules\ValidateUser;
use App\Rules\ValidateWorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateWorkShiftRequest extends BaseFormRequest
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
                new ValidateUser($all_manager_department_ids),
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
