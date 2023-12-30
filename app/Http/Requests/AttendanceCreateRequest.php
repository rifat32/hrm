<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCreateRequest extends FormRequest
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
            'note' => 'nullable|string',
            'in_geolocation' => 'nullable|string',
            'out_geolocation' => 'nullable|string',


            'in_time' => 'nullable|date_format:H:i:s',
            'out_time' => 'nullable|date_format:H:i:s|after_or_equal:in_time',



            'in_date' => 'required|date',
            'does_break_taken' => "required|boolean"
        ];
    }
}
