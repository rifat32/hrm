<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\Payrun;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PayrollCreateRequest extends BaseFormRequest
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
            'payrun_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {
                    $exists = Payrun::where('id', $value)
                        ->where('payruns.business_id', '=', auth()->user()->business_id)
                        ->where(function($query) use($all_manager_department_ids) {
                            $query->whereHas("departments", function($query) use($all_manager_department_ids) {
                                $query->whereIn("departments.id",$all_manager_department_ids);
                             })
                             ->orWhereHas("users.departments", function($query) use($all_manager_department_ids) {
                                $query->whereIn("departments.id",$all_manager_department_ids);
                             });
                        })
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'users' => 'present|array',
            'users.*' => [
                "numeric",
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


                  $exists =  User::where(
                    [
                        "users.id" => $value,
                        "users.business_id" => auth()->user()->business_id

                    ])
                    ->whereHas("departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids);
                     })

                    //  ->where(function($query)  {
                    //     $query->whereHas("departments.payrun_department",function($query)  {
                    //         $query->where("payrun_departments.payrun_id", $this->payrun_id);
                    //     })
                    //     ->orWhereHas("payrun_user", function($query)   {
                    //         $query->where("payrun_users.payrun_id", $this->payrun_id);
                    //     });
                    // })


                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }



                },

            ],
            "start_date" => "nullable|date",
            "end_date" => "nullable|date",


        ];
    }
}
