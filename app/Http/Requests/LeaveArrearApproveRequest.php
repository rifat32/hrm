<?php

namespace App\Http\Requests;

use App\Rules\ValidateLeaveRecordId;
use Illuminate\Foundation\Http\FormRequest;

class LeaveArrearApproveRequest extends FormRequest
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
        $all_manager_department_ids = $this->get_all_departments_of_manager();
        return [
            'leave_record_id' => [
                'required',
                'numeric',
                new ValidateLeaveRecordId($all_manager_department_ids),

            ],
        ];
    }
}
