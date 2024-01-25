<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CandidateUpdateRequest extends BaseFormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('candidates')
                        ->where('id', $value)
                        ->where('candidates.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'experience_years' => 'required|integer',

           'education_level' => 'nullable|string|in:no_formal_education,primary_education,secondary_education_or_high_school,ged,vocational_qualification,bachelor_degree,master_degree,doctorate_or_higher',

           "job_platform" => 'required|string',


            'cover_letter' => 'nullable|string',
            'application_date' => 'required|date',
            'interview_date' => 'nullable|date|after:application_date',
            'feedback' => 'required|string',
            'status' => 'required|in:applied,progress,interview_stage_1,interview_stage_2,final_interview,rejected,job_offered,hired',
            'job_listing_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('job_listings')
                        ->where('id', $value)
                        ->where('job_listings.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'attachments' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [

            'status.in' => 'Invalid value for status. Valid values are: applied,progress, interview_stage_1, interview_stage_2, final_interview, rejected, job_offered, hired.',
            // ... other custom messages
        ];
    }
}
