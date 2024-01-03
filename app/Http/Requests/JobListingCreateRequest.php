<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class JobListingCreateRequest extends FormRequest
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
            'title' => 'required|string',
            'description' => 'required|string',
            'location' => 'required|string',
            'salary_range' => 'required|string',
            'required_skills' => 'required|string',
            'application_deadline' => 'required|date',
            'posted_on' => 'required|date',

            'job_platforms' => 'required|array',
            'job_platforms.*' =>  [
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('job_platforms')
                    ->where('id', $value)
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }
                },
            ],


            'department_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('departments')
                        ->where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
        ];
    }
}
