<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserSponsorshipHistoryCreateRequest extends BaseFormRequest
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
        return [
            'user_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $all_manager_department_ids = $this->get_all_departments_of_manager();

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
            "date_assigned" => 'required|date',
            "expiry_date" => 'required|date',
            // "status" => 'required|in:pending,approved,denied,visa_granted',
            "note" => 'required|string',
            "certificate_number" => 'required|string',
            "current_certificate_status" => 'required|in:unassigned,assigned,visa_applied,visa_rejected,visa_grantes,withdrawal',
            "is_sponsorship_withdrawn" => 'required|boolean',


            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ];
    }
}
