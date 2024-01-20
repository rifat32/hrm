<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\EmployeeSponsorshipHistory;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserSponsorshipHistoryUpdateRequest extends FormRequest
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

                    $exists = EmployeeSponsorshipHistory::where('id', $value)
                        ->where('employee_sponsorship_histories.user_id', '=', $this->user_id)
                        ->where('employee_sponsorship_histories.is_manual', '=', 1)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'user_id' => [
                'required',
                'numeric',
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



            "date_assigned" => 'required|date',
            "expiry_date" => 'required|date',
            "status" => 'required|in:pending,approved,denied,visa_granted',
            "note" => 'required|string',
            "certificate_number" => 'required|string',
            "current_certificate_status" => 'required|in:unassigned,assigned,visa_applied,visa_rejected,visa_grantes,withdrawal',
            "is_sponsorship_withdrawn" => 'required|boolean',


            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ];
    }
}
