<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserPayslipCreateRequest extends FormRequest
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


            "payroll_id" => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail)  {


                  $exists =  Payroll::where(
                    [
                        "payrolls.user_id" => $this->user_id,

                    ])
                    ->whereHas("departments", function($query)  {
                        $query->whereIn("departments.id");
                     })
                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }


                },
            ],


            'month' => 'required|integer',
            'year' => 'required|integer',
            'payment_amount' => 'required|numeric',
            'payment_date' => 'required|date',
            'payslip_file' => 'nullable|string',
            'payment_record_file' => 'present|array',





        ];



    }
}
