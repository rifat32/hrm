<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\PayrunDepartment;
use App\Models\PayrunUser;
use App\Models\User;
use App\Rules\ValidateDepartment;
use Illuminate\Foundation\Http\FormRequest;

class PayrunCreateRequest extends BaseFormRequest
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
            'period_type' => 'required|in:weekly,monthly,customized',
            'start_date' => 'nullable|required_if:period_type,customized|date',
            'end_date' => 'nullable|required_if:period_type,customized|date',
            'consider_type' => 'required|in:hour,daily_log,none',
            'consider_overtime' => 'required|boolean',
            'notes' => 'nullable|string',
            'departments' => 'present|array|size:0',
            'departments.*' => [
                'numeric',
                new ValidateDepartment($all_manager_department_ids),
                function ($attribute, $value, $fail) use($all_manager_department_ids) {
                        $payrun_department = PayrunDepartment::where([
                            "department_id" => $value
                        ])
                        ->first();
                        if($payrun_department) {
                            $fail($attribute . " is invalid. Payrun already created for this department.");
                            return;
                        }
                },
            ],
            'users' => 'present|array|size:0',
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
                $fail($attribute . " is invalid.");
                return;
            }



            $payrun_user = PayrunUser::where([
                "user_id" => $user->id
            ])
            ->first();
            if($payrun_user) {
                $fail($attribute . " is invalid. Payrun already created for this user.");
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
