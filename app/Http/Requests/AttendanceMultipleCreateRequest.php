<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLocation;
use App\Rules\UniqueAttendanceDate;
use App\Rules\ValidProjectId;
use App\Rules\ValidUserId;
use App\Rules\ValidWorkLocationId;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceMultipleCreateRequest extends BaseFormRequest
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
            'user_id' => [
                'required',
                'numeric',
                new ValidUserId($all_manager_department_ids),
            ],


            'attendance_details' => 'required|array',

            'attendance_details.*.note' => 'nullable|string',
            'attendance_details.*.in_geolocation' => 'nullable|string',
            'attendance_details.*.out_geolocation' => 'nullable|string',

            'attendance_details.*.in_time' => 'nullable|date_format:H:i:s',
            'attendance_details.*.out_time' => [
                'required',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $inTime = request('attendance_details')[$index]['in_time'] ?? false;

                    if ($value !== null && strtotime($value) < strtotime($inTime)) {
                        $fail($attribute . " must be after or equal to in_time.");
                    }


                },
            ],

            'attendance_details.*.in_date' => [
                 "required",
                 "date",
                 new UniqueAttendanceDate(NULL, $this->user_id),

            ],



            'attendance_details.*.does_break_taken' => "required|boolean",


            'attendance_details.*.project_id' => [
                'required',
                'numeric',
                new ValidProjectId
            ],


            'attendance_details.*.work_location_id' => [
                "required",
                'numeric',
                new ValidWorkLocationId
            ],



        ];
    }
}
