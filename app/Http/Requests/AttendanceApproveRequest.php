<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceApproveRequest extends FormRequest
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
            'attendance_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $all_manager_department_ids = [];
                    $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
                    foreach ($manager_departments as $manager_department) {
                        $all_manager_department_ids[] = $manager_department->id;
                        $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
                    }

                    $exists = Attendance::where('attendances.id', $value)
                        ->where('attendances.business_id', '=', auth()->user()->business_id)
                        ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                            $query->whereIn("departments.id",$all_manager_department_ids);
                         })
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],

            "is_approved" => "required|boolean"
        ];
    }
}
