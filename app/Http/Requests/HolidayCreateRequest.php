<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\User;
use App\Rules\ValidateDepartment;
use Illuminate\Foundation\Http\FormRequest;

class HolidayCreateRequest extends BaseFormRequest
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
            'name' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'repeats_annually' => 'required|boolean',


            'departments' => 'present|array',
            'departments.*' => [
                'numeric',
                new ValidateDepartment($all_manager_department_ids)
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
                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }



                },

            ],
        ];
    }
}
