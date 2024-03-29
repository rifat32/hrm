<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserPayslipUpdateRequest extends BaseFormRequest
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

            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


                  $exists =  Payslip::where(
                    [
                        "payslips.id" => $value,
                        "user_id" => $this->user_id
                    ])
                    ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids);
                     })
                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }


                },
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
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


                  $exists =  Payroll::where(
                    [
                        "payrolls.user_id" => $this->user_id,

                    ])
                    ->whereHas("user.departments", function($query) use($all_manager_department_ids)  {
                        $query->whereIn("departments.id",$all_manager_department_ids);
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
            'payment_notes' => 'nullable|string',
            'payment_date' => 'required|date',
            'payslip_file' => 'nullable|string',
            'payment_record_file' => 'present|array',

            'gross_pay' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'employee_ni_deduction' => 'required|numeric|min:0',
            'employer_ni' => 'required|numeric|min:0',
            'payment_method' => ['required', 'string', 'in:bank_transfer,cash,cheque,other'],


        ];
    }
    public function messages()
    {
        return [
            'payment_method.required' => 'The payment method field is required.',
            'payment_method.in' => 'Invalid payment method selected. Valid options are: bank_transfer, cash, cheque, or other.',
        ];
    }
}
