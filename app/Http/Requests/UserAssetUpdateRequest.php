<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Models\UserAsset;
use Illuminate\Foundation\Http\FormRequest;

class UserAssetUpdateRequest extends BaseFormRequest
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
                'nullable',
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {
                    $exists = UserAsset:: where(
                        [
                            "user_assets.id" => $value,
                            "user_assets.business_id" => auth()->user()->business_id

                        ])
                        // ->where('user_assets.user_id', '=', $this->user_id)
                        // ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                        //     $query->whereIn("departments.id",$all_manager_department_ids);
                        //  })
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'name' => "required|string",
            'code' => "required|string",
            "is_working" => "required|boolean",
            "status" => "required|string|in:available,assigned,damaged,lost,reserved,repair_waiting",

            'serial_number' => "required|string",
            'type' => "required|string",
            'image' => "nullable|string",
            'date' => "required|date",
            'note' => "required|string",
        ];
    }
    public function messages()
    {
        return [

            'status.in' => 'Invalid value for status. Valid values are: assigned, damaged, lost, reserved, repair_waiting.',


            // ... other custom messages
        ];
    }
}
