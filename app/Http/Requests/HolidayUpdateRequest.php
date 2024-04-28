<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\User;
use App\Rules\ValidateDepartment;
use App\Rules\ValidUserId;
use Illuminate\Foundation\Http\FormRequest;

class HolidayUpdateRequest extends BaseFormRequest
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
            'id' => 'required|numeric',
             'name' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'repeats_annually' => 'required|boolean',
            'departments' => 'present|array',
            'departments.*' => [
                'numeric',
                new ValidateDepartment($all_manager_department_ids)
            ],
            'users' => 'present|array',
            'users.*' => [
                "numeric",
                new ValidUserId($all_manager_department_ids)

            ],
        ];
    }
}
