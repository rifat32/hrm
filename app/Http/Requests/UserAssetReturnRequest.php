<?php

namespace App\Http\Requests;

use App\Models\UserAsset;
use App\Rules\ValidUserId;
use Illuminate\Foundation\Http\FormRequest;

class UserAssetReturnRequest extends FormRequest
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
        $all_manager_department_ids = $this->get_all_departments_of_manager();
        return [
            'user_id' => [
                'nullable',
                'numeric',
                new ValidUserId($all_manager_department_ids),
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
                        // ->whereHas("user.department_user.department", function($query) use($all_manager_department_ids) {
                        //     $query->whereIn("departments.id",$all_manager_department_ids);
                        //  })
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
        ];
    }
}
