<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateJoiningDateRequest extends BaseFormRequest
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


                  $exists =  User::where(
                    [
                        "users.id" => $value,
                        "users.business_id" => auth()->user()->business_id

                    ])
                    ->when(!empty(auth()->user()->business_id), function($query) use($all_manager_department_ids) {
                        $query->whereHas("departments", function($query) use($all_manager_department_ids) {
                            $query->whereIn("departments.id",$all_manager_department_ids);
                         });
                    })

                     ->first();

            if (!$exists) {
                $fail($attribute . " is invalid.");
                return;
            }



                },
            ],



            'joining_date' => [
                "required",
                'date',
                function ($attribute, $value, $fail) {

                   $joining_date = Carbon::parse($value);
                   $start_date = Carbon::parse(auth()->user()->business->start_date);

                   if ($joining_date->lessThan($start_date)) {
                       $fail("The $attribute must not be before the start date of the business.");
                   }

                },
            ],

            
        ];
    }
}
