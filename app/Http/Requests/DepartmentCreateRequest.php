<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkLocation;
use App\Rules\ValidWorkLocationId;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentCreateRequest extends BaseFormRequest
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
        $all_manager_department_ids = [];
        $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
        foreach ($manager_departments as $manager_department) {
            $all_manager_department_ids[] = $manager_department->id;
            $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
        }
        return [
            'name' => [
                "required",
                "string",
                function ($attribute, $value, $fail) use($all_manager_department_ids){
                    $department_exists_with_name =   Department::where([
                        "name" => $value,
                        "business_id" => auth()->user()->business_id
                    ])
                    ->exists();
                    if($department_exists_with_name) {
                        $fail($attribute . " is invalid. A department with the same name already exists for your business." );
                        return;

                    }

                  },

            ],
            'work_location_id' => [
                "required",
                'numeric',
                new ValidWorkLocationId()
            ],
            'description' => 'nullable|string',

            'manager_id' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids){


                  $exists =  User::where(
                    [
                        "users.id" => $value,
                        "users.business_id" => auth()->user()->business_id

                    ])
                    ->whereHas("departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids);
                     })
                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }

                },
            ],

            'parent_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


                    if(!empty($value)){
                        $parent_department = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->first();

                    if (!$parent_department) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                    if(!in_array($parent_department->id,$all_manager_department_ids)){
                        $fail($attribute . " is invalid. You don't have access to this department.");
                        return;
                    }

                    }

                },
            ],

        ];
    }
}
