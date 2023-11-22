<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class TaskCreateRequest extends FormRequest
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
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'end_date' => 'nullable|date|after_or_equal:due_date',
            'status' => 'required|in:pending,progress,completed',
            'project_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('projects')
                        ->where('id', $value)
                        ->where('projects.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'parent_task_id' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('tasks')
                        ->where('id', $value)
                        ->where('tasks.business_id', '=', auth()->user()->business_id)
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
            'due_date.after_or_equal' => 'Due date must be after or equal to the start date.',
            'end_date.after_or_equal' => 'End date must be after or equal to the due date.',
            'status.in' => 'Invalid value for status. Valid values are: pending, progress, completed.',
            'project_id.exists' => 'Invalid project selected.',
            'parent_task_id.exists' => 'Invalid parent task selected.',

        ];
    }
}
