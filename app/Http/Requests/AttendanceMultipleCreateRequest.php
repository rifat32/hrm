<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceMultipleCreateRequest extends BaseFormRequest
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
            'user_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


                  $exists =  User::where(
                    [
                        "users.id" => $value,
                        "users.business_id" => auth()->user()->business_id

                    ])
                    ->whereHas("departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids);
                     })
                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }



                },
            ],


            'attendance_details' => 'required|array',

            'attendance_details.*.note' => 'nullable|string',
            'attendance_details.*.in_geolocation' => 'nullable|string',
            'attendance_details.*.out_geolocation' => 'nullable|string',

            'attendance_details.*.in_time' => 'nullable|date_format:H:i:s',
            'attendance_details.*.out_time' => [
                'required',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $inTime = request('attendance_details')[$index]['in_time'] ?? false;

                    if ($value !== null && strtotime($value) < strtotime($inTime)) {
                        $fail($attribute . " must be after or equal to in_time.");
                    }


                },
            ],

            'attendance_details.*.in_date' => [
                 "required",
                 "date",
                 function ($attribute, $value, $fail) {
                    $exists = Attendance::where('attendances.user_id', $this->id)
                    ->whereDate('attendances.business_id', '=', auth()->user()->business_id)
                    ->exists();

                if ($exists) {
                    $fail($attribute . " is invalid.");
                }

                },

            ],



            'attendance_details.*.does_break_taken' => "required|boolean",


            'attendance_details.*.project_id' => [
                'present',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = Project::
                        where('id', $value)
                        ->where('projects.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],


            'attendance_details.*.work_location_id' => [
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



        ];
    }
}
