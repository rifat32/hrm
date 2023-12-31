<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Models\UserJobHistory;
use Illuminate\Foundation\Http\FormRequest;

class UserJobHistoryUpdateRequest extends FormRequest
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
                $fail("$attribute is invalid.");
                return;
            }



                },
            ],
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = UserJobHistory::where('id', $value)
                        ->where('user_job_histories.user_id', '=', $this->user_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'company_name' => 'required|string',
            'job_title' => 'required|string',
            'employment_start_date' => 'required|date',
            'employment_end_date' => 'nullable|date|after_or_equal:employment_start_date',
            'responsibilities' => 'nullable|string',
            'supervisor_name' => 'nullable|string',
            'contact_information' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'work_location' => 'nullable|string',
            'achievements' => 'nullable|string',
        ];
    }
}
