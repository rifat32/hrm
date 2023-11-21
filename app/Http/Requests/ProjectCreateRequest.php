<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ProjectCreateRequest extends FormRequest
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
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:pending,progress,completed',
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

    public function messages()
    {
        return [
            'end_date.after_or_equal' => 'End date must be after or equal to the start date.',
            'status.in' => 'Invalid value for status. Valid values are: pending, progress, completed.',
            'department_id.exists' => 'Invalid department selected.',
            // ... other custom messages
        ];
    }
}
