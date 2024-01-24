<?php

namespace App\Http\Requests;

use App\Models\JobType;
use Illuminate\Foundation\Http\FormRequest;

class JobTypeCreateRequest extends BaseFormRequest
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
            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = JobType::where("job_types.name",$value)

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

                    if ($exists) {
                        $fail("$attribute is already exist.");
                    }


                },
            ],
        ];


return $rules;

    }
}
