<?php

namespace App\Http\Requests;

use App\Rules\UniqueAttendanceDate;
use App\Rules\ValidProjectId;
use App\Rules\ValidWorkLocationId;
use Illuminate\Foundation\Http\FormRequest;

class SelfAttendanceCreateRequest extends FormRequest
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







            'note' => 'nullable|string',
            'in_geolocation' => 'nullable|string',
            'out_geolocation' => 'nullable|string',

            'attendance_records' => 'required|array',
            'attendance_records.*.in_time' => 'required|date_format:H:i:s',
            'attendance_records.*.out_time' => [
                'required',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $inTime = request('attendance_records')[$index]['in_time'] ?? false;

                    if ($value !== null && strtotime($value) < strtotime($inTime)) {
                        $fail($attribute . " must be after or equal to in_time.");
                    }


                },
            ],









            'in_date' => [
                'required',
                'date',
                new UniqueAttendanceDate(NULL, $this->user_id),
            ],


            'does_break_taken' => "required|boolean",

            'project_id' => [
                'required',
                'numeric',
                new ValidProjectId,
            ],


            'work_location_id' => [
                "required",
                'numeric',
                new ValidWorkLocationId
            ],
        ];
    }
}
