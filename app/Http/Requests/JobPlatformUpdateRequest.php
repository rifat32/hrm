<?php

namespace App\Http\Requests;

use App\Models\JobPlatform;
use Illuminate\Foundation\Http\FormRequest;

class JobPlatformUpdateRequest extends FormRequest
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
        $rules = [
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $job_platform_query_params = [
                        "id" => $this->id,
                    ];
                    $job_platform = JobPlatform::where($job_platform_query_params)
                        ->first();
                    if (!$job_platform) {
                            // $fail("$attribute is invalid.");
                            $fail("no job platform found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($job_platform->business_id != NULL || $job_platform->is_default != 1)) {
                                // $fail("$attribute is invalid.");
                                $fail("You do not have permission to update this job platform due to role restrictions.");

                          }

                        } else {
                            if(($job_platform->business_id != NULL || $job_platform->is_default != 0 || $job_platform->created_by != auth()->user()->id)) {
                                // $fail("$attribute is invalid.");
                                $fail("You do not have permission to update this job platform due to role restrictions.");

                          }
                        }

                    } else {
                        if(($job_platform->business_id != auth()->user()->business_id || $job_platform->is_default != 0)) {
                               // $fail("$attribute is invalid.");
                            $fail("You do not have permission to update this job platform due to role restrictions.");
                        }
                    }




                },
            ],
            'name' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = JobPlatform::where("job_platforms.name",$value)
                        ->whereNotIn("id",[$this->id])

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

                    if ($exists) {
                        $fail("$attribute is already exist.");
                    }


                },
            ],

            'description' => 'nullable|string',
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:job_platforms,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     $rules['name'] .= '|unique:job_platforms,name,'.$this->id.',id,is_default,' . (auth()->user()->hasRole('superadmin') ? 1 : 0);
        // }

return $rules;
    }
}
