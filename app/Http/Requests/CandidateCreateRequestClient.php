<?php

namespace App\Http\Requests;

use App\Models\JobPlatform;
use App\Rules\ValidateJobListing;
use App\Rules\ValidateJobListingClient;
use App\Rules\ValidateJobPlatform;
use App\Rules\ValidateJobPlatformClient;
use App\Rules\ValidateRecruitmentProcessId;
use App\Rules\ValidateRecruitmentProcessIdClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CandidateCreateRequestClient extends BaseFormRequest
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
            'business_id' => 'required|numeric',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'experience_years' => 'required|integer',
            'education_level' => 'nullable|string|in:no_formal_education,primary_education,secondary_education_or_high_school,ged,vocational_qualification,bachelor_degree,master_degree,doctorate_or_higher',

            'job_platforms' => 'required|array',
            'job_platforms.*' => [
                "required",
                'numeric',
                new ValidateJobPlatformClient(),
            ],




            'cover_letter' => 'nullable|string',
            'application_date' => 'required|date',
            'interview_date' => 'nullable|date|after:application_date',
            'feedback' => 'required|string',


            'status' => 'required|in:applied,in_progress,interview_stage_1,interview_stage_2,final_interview,rejected,job_offered,hired',
            'job_listing_id' => [
                'required',
                'numeric',
                new ValidateJobListingClient()
            ],

            'attachments' => 'present|array',
            'attachments.*' => 'string',


        'recruitment_processes' => "present|array",
        'recruitment_processes.*.recruitment_process_id' => [
            "required",
            'numeric',
            new ValidateRecruitmentProcessIdClient()
        ],
        'recruitment_processes.*.description' => "nullable|string",
        'recruitment_processes.*.attachments' => "present|array",


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
