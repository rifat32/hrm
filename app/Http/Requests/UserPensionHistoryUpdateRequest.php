<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\EmployeePensionHistory;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserPensionHistoryUpdateRequest extends BaseFormRequest
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
                function ($attribute, $value, $fail) {
                    $exists = EmployeePensionHistory::where('id', $value)
                        ->where('employee_pension_histories.user_id', '=', $this->user_id)

                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'pension_eligible' => 'required|boolean',
            'pension_letters' => 'present|array',
            'pension_scheme_status' => 'nullable|required_if:pension_eligible,1|string|in:opt_in,opt_out',
            'pension_enrollment_issue_date' => 'nullable|required_if:pension_scheme_status,opt_in|date',
            'pension_scheme_opt_out_date' => 'nullable|required_if:pension_scheme_status,opt_out|date',
            'pension_re_enrollment_due_date' => 'nullable|required_if:pension_scheme_status,opt_out|date',




            'user_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($all_manager_department_ids) {


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
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ];
    }

    public function messages()
    {
        return [
            'pension_scheme_status.required' => 'The pension scheme status is required.',
            'pension_scheme_status.in' => 'The pension scheme status must be either "opt_in" or "opt_out".',
            'pension_scheme_opt_out_date.required_if' => 'The pension opt-out date is required when pension scheme status is "opt_out".',
            'pension_re_enrollment_due_date.required_if' => 'The re-enrollment due date is required when pension scheme status is "opt_out".',
        ];
    }
}
