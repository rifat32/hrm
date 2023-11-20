<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CandidateCreateRequest extends FormRequest
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
            'education_level' => 'required|string',
            'cover_letter' => 'nullable|string',
            'application_date' => 'required|date',
            'interview_date' => 'nullable|date|after:application_date',
            'feedback' => 'required|string',
            'status' => 'required|in:review,interviewed,hired',
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

            'status.in' => 'Invalid value for status. Valid values are: review, interviewed, hired.',
            // ... other custom messages
        ];
    }
}
