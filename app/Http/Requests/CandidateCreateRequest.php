<?php

namespace App\Http\Requests;

use App\Models\JobPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CandidateCreateRequest extends BaseFormRequest
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
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'experience_years' => 'required|integer',
            'education_level' => 'nullable|string|in:no_formal_education,primary_education,secondary_education_or_high_school,ged,vocational_qualification,bachelor_degree,master_degree,doctorate_or_higher',

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
                        $fail($attribute . " is invalid.");
                    }


                },
            ],




            'cover_letter' => 'nullable|string',
            'application_date' => 'required|date',
            'interview_date' => 'nullable|date|after:application_date',
            'feedback' => 'required|string',


            'status' => 'required|in:applied,in_progress,interview_stage_1,interview_stage_2,final_interview,rejected,job_offered,hired',
            'job_listing_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('job_listings')
                        ->where('id', $value)
                        ->where('job_listings.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'attachments' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [

            'status.in' => 'Invalid value for status. Valid values are: applied,in_progress, interview_stage_1, interview_stage_2, final_interview, rejected, job_offered, hired.',
            'education_level.in' => 'Invalid value for status. Valid values are: no_formal_education,primary_education,secondary_education_or_high_school,ged,vocational_qualification,bachelor_degree,master_degree,doctorate_or_higher.',
            // ... other custom messages
        ];
    }
}
