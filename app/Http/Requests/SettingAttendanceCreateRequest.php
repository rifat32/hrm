<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SettingAttendanceCreateRequest extends BaseFormRequest
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
            'punch_in_time_tolerance' => 'nullable|numeric|min:0',
            'work_availability_definition' => 'nullable|numeric|min:0',
            'punch_in_out_alert' => 'nullable|boolean',
            'punch_in_out_interval' => 'nullable|numeric|min:0',
            'alert_area' => 'nullable|array',
            'alert_area.*' => 'string',
            'service_name' => 'nullable|string',
            'api_key'  => 'nullable|string',

            'auto_approval' => 'nullable|boolean',
            'special_users' => 'present|array',
            'special_users.*' => [
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
                $fail($attribute . " is invalid.");
                return;
            }



                },

            ],


            'special_roles' => 'present|array',

            'special_roles.*' => [
                'numeric',
                function ($attribute, $value, $fail) {
                    $role = Role::where("id", $value)
                        ->first();


                    if (!$role) {
                        // $fail($attribute . " is invalid.");
                        $fail("Role does not exists.");
                    }
                    if (empty(auth()->user()->business_id)) {
                        if (!(empty($role->business_id) || $role->is_default == 1)) {
                            // $fail($attribute . " is invalid.");
                            $fail("Role belongs to another business.");
                        }
                    } else {
                        if ($role->business_id != auth()->user()->business_id) {
                            // $fail($attribute . " is invalid.");
                            $fail("Role belongs to another business.");
                        }
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'punch_in_out_alert.boolean' => 'The :attribute field must be a boolean.',
            'alert_area.array' => 'The :attribute field must be an array.',
            'alert_area.*.string' => 'Each item in :attribute must be a string.',
            'auto_approval.boolean' => 'The :attribute field must be a boolean.',
        ];
    }
}
