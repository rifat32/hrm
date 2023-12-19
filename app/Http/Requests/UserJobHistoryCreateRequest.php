<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserJobHistoryCreateRequest extends FormRequest
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
            'user_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = User::where('id', $value)
                        ->where('users.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'company_name' => 'required|string',
            'job_title' => 'required|string',
            'employment_start_date' => 'required|date',
            'employment_end_date' => 'nullable|date|after_or_equal:employment_start_date',
            'responsibilities' => 'nullable|string',
            'supervisor_name' => 'nullable|string',
            'contact_information' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'work_location' => 'nullable|string',
            'achievements' => 'nullable|string',
        ];
    }
}
