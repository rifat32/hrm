<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Payrun;
use App\Models\PayrunDepartment;
use App\Models\PayrunUser;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PayrunUpdateRequest extends FormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {
                    $exists = Payrun::where('id', $value)
                        ->where('payruns.business_id', '=', auth()->user()->id)
                        ->whereHas("departments", function($query) use($all_manager_department_ids) {
                            $query->whereIn("departments.id",$all_manager_department_ids);
                         })
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],

            'period_type' => 'required|in:weekly,monthly,customized',
            'start_date' => 'nullable|required_if:period_type,customized|date',
            'end_date' => 'nullable|required_if:period_type,customized|date',
            'consider_type' => 'required|in:hour,daily_log,none',
            'consider_overtime' => 'required|boolean',
            'notes' => 'nullable|string',
            'departments' => 'present|array',
            'departments.*' => [
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
                       $payrun_department = PayrunDepartment::where([
                            "department_id" => $department->id
                        ])
                        ->whereNotIn("payrun_id",[$this->id])
                        ->first();
                        if($payrun_department) {
                            $fail("$attribute is invalid. Payrun already created for this department.");
                            return;
                        }
                },
            ],
            'users' => 'present|array',
            'users.*' => [
                "numeric",
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


                  $user =  User::where(
                    [
                        "users.id" => $value,
                        "users.business_id" => auth()->user()->business_id

                    ])

                    ->whereHas("departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids);
                     })
                     ->first();

            if (!$user) {
                $fail("$attribute is invalid.");
                return;
            }

            $payrun_user = PayrunUser::where([
                "user_id" => $user->id
            ])
            ->whereNotIn("payrun_id",[$this->id])
            ->first();
            if($payrun_user) {
                $fail("$attribute is invalid. Payrun already created for this user.");
                return;
            }

                },

            ],
        ];
    }

    // Optionally, you can customize error messages
    public function messages()
    {
        return [
            'period_type.required' => 'The period type field is required.',
            'period_type.in' => 'Invalid value for period type. Valid values are weekly,monthly,customized.',
            'start_date.date' => 'Invalid start date format.',
            'end_date.date' => 'Invalid end date format.',
            'consider_type.required' => 'The consider type field is required.',
            'consider_type.in' => 'Invalid value for consider type. Valid values are weekly,monthly,customized.',
            'consider_overtime.required' => 'The consider overtime field is required.',
            'consider_overtime.boolean' => 'The consider overtime field must be a boolean.',
            'notes.string' => 'The notes field must be a string.',
        ];
    }










}
