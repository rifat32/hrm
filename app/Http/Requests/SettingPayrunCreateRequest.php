<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SettingPayrunCreateRequest extends FormRequest
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
            'payrun_period' => 'required|in:monthly,weekly',
            'consider_type' => 'required|in:hour,daily_log,none',
            'consider_overtime' => 'required|boolean',
            'restricted_users' => 'present|array',


            'restricted_users.*' => [
                "numeric",
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


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

            'restricted_departments' => 'present|array',
            'restricted_departments.*' => [
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {
                    $department = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->first();

                        if (!$department) {
                            $fail("$attribute is invalid.");
                            return;
                        }
                        if(!in_array($department->id,$all_manager_department_ids)){
                            $fail("$attribute is invalid. You don't have access to this department.");
                            return;
                        }
                },
            ]

        ];
    }


    public function messages()
    {
        return [
            'payrun_period.required' => 'The :attribute field is required.',
            'payrun_period.in' => 'The :attribute must be either "monthly" or "weekly".',
            'consider_type.required' => 'The :attribute field is required.',
            'consider_type.in' => 'The :attribute must be either "hour", "daily_log", or "none".',
            'consider_overtime.required' => 'The :attribute field is required.',
            'consider_overtime.boolean' => 'The :attribute field must be a boolean.',
        ];
    }



}
