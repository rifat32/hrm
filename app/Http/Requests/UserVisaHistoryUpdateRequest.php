<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\EmployeeVisaDetailHistory;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserVisaHistoryUpdateRequest extends BaseFormRequest
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
                    $exists = EmployeeVisaDetailHistory::where('id', $value)
                        ->where('employee_visa_detail_histories.user_id', '=', $this->user_id)
                        ->where('employee_visa_detail_histories.is_manual', '=', 1)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'user_id' => [
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
                $fail($attribute . " is invalid.");
                return;
            }



                },
            ],
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',


            'BRP_number' => 'required|string',
            'visa_issue_date' => 'required|date',
            'visa_expiry_date' => 'required|date',
            'place_of_issue' => 'required|string',
            'visa_docs' => 'required|array',
            'visa_docs.*.file_name' => 'required|string',
            'visa_docs.*.description' => 'required|string',

        ];
    }
}
