<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\Leave;
use App\Models\SettingLeaveType;
use App\Models\User;
use App\Rules\ValidSettingLeaveType;
use Illuminate\Foundation\Http\FormRequest;

class LeaveUpdateRequest extends BaseFormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = Leave::where('id', $value)
                    ->where("user_id",$this->user_id)
                        ->exists();
                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],

            'leave_duration' => 'required|in:single_day,multiple_day,half_day,hours',
            'day_type' => 'nullable|in:first_half,last_half',
            'leave_type_id' => [
                'required',
                'numeric',
                new ValidSettingLeaveType($this->user_id,$this->id),
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


                ->whereNotIn("users.id",[auth()->user()->id])


                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }



                },
            ],
            'date' => 'nullable|required_if:leave_duration,single_day,half_day,hours|date',
            'note' => 'required|string',

            'start_date' => 'nullable|required_if:leave_duration,multiple_day',
            'end_date' => 'nullable|required_if:leave_duration,multiple_day|after_or_equal:start_date',

            'start_time' => 'nullable|required_if:leave_duration,hours|date_format:H:i:s',
            'end_time' => 'nullable|required_if:leave_duration,hours|date_format:H:i:s|after_or_equal:start_time',

            'attachments' => 'present|array',
            "hourly_rate" => "required|numeric"
        ];
    }

    public function messages()
{
    return [
        'leave_duration.required' => 'The leave duration field is required.',
        'leave_duration.in' => 'Invalid value for leave duration. Valid values are: single_day, multiple_day, half_day, hours.',
        'day_type.in' => 'Invalid value for day type. Valid values are: first_half, last_half.',
        // ... other custom messages
    ];
}
}
