<?php

namespace App\Http\Requests;

use App\Models\Leave;
use App\Models\SettingLeaveType;
use Illuminate\Foundation\Http\FormRequest;

class LeaveUpdateRequest extends FormRequest
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
                    $exists = Leave::where('id', $value)
                        ->exists();
                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],

            'leave_duration' => 'required|in:single_day,multiple_day,half_day,hours',
            'day_type' => 'nullable|in:first_half,last_half',
            'leave_type_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $created_by  = NULL;
                if(auth()->user()->business) {
                    $created_by = auth()->user()->business->created_by;
                }

                $exists = SettingLeaveType::where("setting_leave_types.id",$value)
                ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                    if (auth()->user()->hasRole('superadmin')) {
                        return $query->where('setting_leave_types.business_id', NULL)
                            ->where('setting_leave_types.is_default', 1)
                            ->where('setting_leave_types.is_active', 1);

                    } else {
                        return $query->where('setting_leave_types.business_id', NULL)
                            ->where('setting_leave_types.is_default', 1)
                            ->where('setting_leave_types.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) {
                                $q->whereIn("disabled_setting_leave_types.created_by", [auth()->user()->id]);
                            })

                            ->orWhere(function ($query) use($value)  {
                                $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', NULL)
                                    ->where('setting_leave_types.is_default', 0)
                                    ->where('setting_leave_types.created_by', auth()->user()->id)
                                    ->where('setting_leave_types.is_active', 1);


                            });
                    }
                })
                    ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                        return $query->where('setting_leave_types.business_id', NULL)
                            ->where('setting_leave_types.is_default', 1)
                            ->where('setting_leave_types.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) use($created_by) {
                                $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                            })
                            ->whereDoesntHave("disabled", function($q)  {
                                $q->whereIn("disabled_setting_leave_types.business_id",[auth()->user()->business_id]);
                            })

                            ->orWhere(function ($query) use( $created_by, $value){
                                $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', NULL)
                                    ->where('setting_leave_types.is_default', 0)
                                    ->where('setting_leave_types.created_by', $created_by)
                                    ->where('setting_leave_types.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_setting_leave_types.business_id",[auth()->user()->business_id]);
                                    });
                            })
                            ->orWhere(function ($query) use($value)  {
                                $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', auth()->user()->business_id)
                                    ->where('setting_leave_types.is_default', 0)
                                    ->where('setting_leave_types.is_active', 1);

                            });
                    })
                ->exists();

            if (!$exists) {
                $fail("$attribute is invalid.");
            }
                },
            ],
            'employee_id' => 'required|numeric',
            'date' => 'nullable|required_if:leave_duration,single_day,half_day,hours|date',
            'note' => 'required|string',

            'start_date' => 'nullable|required_if:leave_duration,multiple_day|date_format:d-m-Y',
            'end_date' => 'nullable|required_if:leave_duration,multiple_day|date_format:d-m-Y|after_or_equal:start_date',

            'start_time' => 'nullable|required_if:leave_duration,hours|date_format:H:i:s',
            'end_time' => 'nullable|required_if:leave_duration,hours|date_format:H:i:s|after_or_equal:date',

            'attachments' => 'nullable|array',
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
