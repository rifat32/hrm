<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\JobPlatform;
use App\Models\JobType;
use App\Models\WorkLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class JobListingCreateRequest extends BaseFormRequest
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
            'title' => 'required|string',
            'description' => 'required|string',

            'minimum_salary' => 'required|numeric',
            'maximum_salary' => 'required|numeric',
            'experience_level' => 'required|string',
            'required_skills' => 'required|string',
            'application_deadline' => 'required|date',
            'posted_on' => 'required|date',

            'job_platforms' => 'required|array',

            'job_platforms.*' => [
                "required",
                'numeric',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = JobPlatform::where("job_platforms.id",$value)
                        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                            if (auth()->user()->hasRole('superadmin')) {
                                return $query->where('job_platforms.business_id', NULL)
                                    ->where('job_platforms.is_default', 1)
                                    ->where('job_platforms.is_active', 1);

                            } else {
                                return $query->where('job_platforms.business_id', NULL)
                                    ->where('job_platforms.is_default', 1)
                                    ->where('job_platforms.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_job_platforms.created_by", [auth()->user()->id]);
                                    })

                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("job_platforms.id",$value)->where('job_platforms.business_id', NULL)
                                            ->where('job_platforms.is_default', 0)
                                            ->where('job_platforms.created_by', auth()->user()->id)
                                            ->where('job_platforms.is_active', 1);


                                    });
                            }
                        })
                            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                                return $query->where('job_platforms.business_id', NULL)
                                    ->where('job_platforms.is_default', 1)
                                    ->where('job_platforms.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                                        $q->whereIn("disabled_job_platforms.created_by", [$created_by]);
                                    })
                                    ->whereDoesntHave("disabled", function($q)  {
                                        $q->whereIn("disabled_job_platforms.business_id",[auth()->user()->business_id]);
                                    })

                                    ->orWhere(function ($query) use( $created_by, $value){
                                        $query->where("job_platforms.id",$value)->where('job_platforms.business_id', NULL)
                                            ->where('job_platforms.is_default', 0)
                                            ->where('job_platforms.created_by', $created_by)
                                            ->where('job_platforms.is_active', 1)
                                            ->whereDoesntHave("disabled", function($q) {
                                                $q->whereIn("disabled_job_platforms.business_id",[auth()->user()->business_id]);
                                            });
                                    })
                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("job_platforms.id",$value)->where('job_platforms.business_id', auth()->user()->business_id)
                                            ->where('job_platforms.is_default', 0)
                                            ->where('job_platforms.is_active', 1);

                                    });
                            })
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }


                },
            ],




            'department_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {

                    $department = Department::where('id', $value)
                    ->where('departments.business_id', '=', auth()->user()->business_id)
                    ->first();

                    if (!$department) {
                        $fail("$attribute is invalid.");
                        return;
                    }
                    if(!in_array($department->id,$all_manager_department_ids)){
                        $fail("$attribute is invalid. You don't have access to this department.");
                        return;
                    }
                },
            ],


            'job_type_id' => [
                "required",
                'numeric',
                function ($attribute, $value, $fail) {

                    $created_by  = NULL;
                    if(auth()->user()->business) {
                        $created_by = auth()->user()->business->created_by;
                    }

                    $exists = JobType::where("job_types.id",$value)
                    ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                        if (auth()->user()->hasRole('superadmin')) {
                            return $query->where('job_types.business_id', NULL)
                                ->where('job_types.is_default', 1)
                                ->where('job_types.is_active', 1);

                        } else {
                            return $query->where('job_types.business_id', NULL)
                                ->where('job_types.is_default', 1)
                                ->where('job_types.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) {
                                    $q->whereIn("disabled_job_types.created_by", [auth()->user()->id]);
                                })

                                ->orWhere(function ($query) use($value)  {
                                    $query->where("job_types.id",$value)->where('job_types.business_id', NULL)
                                        ->where('job_types.is_default', 0)
                                        ->where('job_types.created_by', auth()->user()->id)
                                        ->where('job_types.is_active', 1);


                                });
                        }
                    })
                        ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                            return $query->where('job_types.business_id', NULL)
                                ->where('job_types.is_default', 1)
                                ->where('job_types.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) use($created_by) {
                                    $q->whereIn("disabled_job_types.created_by", [$created_by]);
                                })
                                ->whereDoesntHave("disabled", function($q)  {
                                    $q->whereIn("disabled_job_types.business_id",[auth()->user()->business_id]);
                                })

                                ->orWhere(function ($query) use( $created_by, $value){
                                    $query->where("job_types.id",$value)->where('job_types.business_id', NULL)
                                        ->where('job_types.is_default', 0)
                                        ->where('job_types.created_by', $created_by)
                                        ->where('job_types.is_active', 1)
                                        ->whereDoesntHave("disabled", function($q) {
                                            $q->whereIn("disabled_job_types.business_id",[auth()->user()->business_id]);
                                        });
                                })
                                ->orWhere(function ($query) use($value)  {
                                    $query->where("job_types.id",$value)->where('job_types.business_id', auth()->user()->business_id)
                                        ->where('job_types.is_default', 0)
                                        ->where('job_types.is_active', 1);

                                });
                        })
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }

                },
            ],
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
                    $fail("$attribute is invalid.");
                }

                },
            ],



        ];
    }
}
