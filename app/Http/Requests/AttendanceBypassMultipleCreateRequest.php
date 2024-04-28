<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLocation;
use App\Rules\ValidUserId;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceBypassMultipleCreateRequest extends BaseFormRequest
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
            'user_ids' => 'present|array',
            'user_ids.*' => [
                "numeric",
             new ValidUserId($all_manager_department_ids)

            ],

            "start_date" => "required|date",
            "end_date" => "required|date|after_or_equal:start_date",




        ];
    }
}
