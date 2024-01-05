<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Models\UserAsset;
use Illuminate\Foundation\Http\FormRequest;

class UserAssetAddExistingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
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
                $fail("$attribute is invalid.");
                return;
            }



                },
            ],
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($all_manager_department_ids) {
                    $exists = UserAsset::where('id', $value)
                        ->where('user_assets.business_id', '=', auth()->user()->business_id)
                        ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                            $query->whereIn("departments.id",$all_manager_department_ids);
                         })
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],

        ];
    }
}
