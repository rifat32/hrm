<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Task;
use App\Rules\ValidateLabelId;
use App\Rules\ValidateTaskCategory;
use App\Rules\ValidateTaskId;
use App\Rules\ValidProjectId;
use App\Rules\ValidUserId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class TaskCreateRequest extends BaseFormRequest
{
    use BasicUtil;
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
        $all_manager_department_ids = $this->get_all_departments_of_manager();
        return [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'end_date' => 'nullable|date|after_or_equal:due_date',
            'status' => 'required|in:pending, in_progress, done, in_review, approved, cancelled, rejected, draft',

            'project_id' => [
                'required',
                'numeric',
                new ValidProjectId()
            ],

            'parent_task_id' => [
                'nullable',
                'numeric',
                new ValidateTaskId(),
            ],

            'task_category_id' => [
                'required',
                'numeric',
                new ValidateTaskCategory(),
            ],

            "assignees" => "required|array",
            "assignees.*" => [
                'numeric',
              new ValidUserId($all_manager_department_ids)
            ],

            'assets' => 'present|array',
            'assets.*' => 'string',


            'labels' => "present|array",
            'labels.*' => [
                'nullable',
                'numeric',
                new ValidateLabelId(),
            ],


            'cover' => "nullable|string",


        ];
    }

    public function messages()
    {
        return [
            'due_date.after_or_equal' => 'Due date must be after or equal to the start date.',
            'end_date.after_or_equal' => 'End date must be after or equal to the due date.',
            'status.in' => 'Invalid value for status. Valid values are: pending, in_progress, done, in_review, approved, cancelled, rejected, draft.',
            'project_id.exists' => 'Invalid project selected.',
            'parent_task_id.exists' => 'Invalid parent task selected.',

        ];
    }
}
