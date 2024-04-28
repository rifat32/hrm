<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkLocation;
use App\Rules\ValidateDepartmentName;
use App\Rules\ValidateParentDepartmentId;
use App\Rules\ValidUserId;
use App\Rules\ValidWorkLocationId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentUpdateRequest extends BaseFormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail)use($all_manager_department_ids){


                    if(!empty($value)){

                        $department = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->first();

                    if (!$department) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                    if(!in_array($department->id,$all_manager_department_ids)){
                        $fail($attribute . " is invalid. You don't have access to this department.");
                        return;
                    }

                    }

                },
            ],
            'name' => [
                "required",
                "string",
                new ValidateDepartmentName(NULL)


            ],
            'work_location_id' => [
                "required",
                'numeric',
                new ValidWorkLocationId()
            ],
            'description' => 'nullable|string',
            'manager_id' => 'nullable|numeric',
            'manager_id' => [
                'nullable',
                'numeric',
                new ValidUserId($all_manager_department_ids)
            ],
            'parent_id' => [
                'required',
                'numeric',
                new ValidateParentDepartmentId($all_manager_department_ids)

            ],

        ];
    }
}
