<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentUpdateRequest extends FormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {


                    if(!empty($value)){
                        $all_manager_department_ids = [];
                        $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
                        foreach ($manager_departments as $manager_department) {
                            $all_manager_department_ids[] = $manager_department->id;
                            $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
                        }
                        $department = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->whereNotIn("id",[$value])
                        ->first();

                    if (!$department) {
                        $fail("$attribute is invalid.");
                        return;
                    }
                    if(!in_array($department->id,$all_manager_department_ids)){
                        $fail("$attribute is invalid. You don't have access to this department.");
                        return;
                    }

                    }

                },
            ],
            'name' => 'required|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|numeric',
            'manager_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $all_manager_department_ids = [];
                    $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
                    foreach ($manager_departments as $manager_department) {
                        $all_manager_department_ids[] = $manager_department->id;
                        $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
                    }

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
                $fail("$attribute is invalid.");
                return;
            }

                },
            ],
            'parent_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {


                    if(!empty($value)){
                        $all_manager_department_ids = [];
                        $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
                        foreach ($manager_departments as $manager_department) {
                            $all_manager_department_ids[] = $manager_department->id;
                            $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
                        }
                        $parent_department = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->first();

                    if (!$parent_department) {
                        $fail("$attribute is invalid.");
                        return;
                    }
                    if(!in_array($parent_department->id,$all_manager_department_ids)){
                        $fail("$attribute is invalid. You don't have access to this department.");
                        return;
                    }

                    }

                },
            ],

        ];
    }
}
