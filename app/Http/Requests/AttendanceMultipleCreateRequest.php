<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceMultipleCreateRequest extends FormRequest
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
            'employee_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = User::where('id', $value)
                        ->where('users.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],

            'attendance_details' => 'required|array',

            'attendance_details.*.note' => 'nullable|string',
            'attendance_details.*.in_time' => 'required|date_format:H:i:s',
            'attendance_details.*.out_time' => [
                'nullable',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $inTime = request('attendance_details')[$index]['in_time'] ?? false;

                    if ($value !== null && strtotime($value) < strtotime($inTime)) {
                        $fail("$attribute must be after or equal to in_time.");
                    }


                },
            ],

            'attendance_details.*.in_date' => [
                 "required",
                 "date",
                 function ($attribute, $value, $fail) {
                    $exists = Attendance::where('attendances.employee_id', $this->id)
                    ->whereDate('attendances.business_id', '=', auth()->user()->business_id)
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }

                },

            ],



            'attendance_details.*.does_break_taken' => "required|boolean"


        ];
    }
}
