<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserRightToWorkHistoryCreateRequest extends FormRequest
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
                $fail($attribute . " is invalid.");
                return;
            }



                },
            ],
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',


            'right_to_work_code' => 'required|string',
            'right_to_work_check_date' => 'required|date',
            'right_to_work_expiry_date' => 'required|date',

            'right_to_work_docs' => 'required|array',
            'right_to_work_docs.*.file_name' => 'required|string',
            'right_to_work_docs.*.description' => 'required|string',


        ];
    }
}
