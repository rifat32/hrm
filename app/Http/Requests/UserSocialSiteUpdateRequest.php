<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\SocialSite;
use App\Models\User;
use App\Models\UserSocialSite;
use Illuminate\Foundation\Http\FormRequest;

class UserSocialSiteUpdateRequest extends BaseFormRequest
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
      return  [



            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = UserSocialSite::where('id', $value)
                        ->where('user_social_sites.user_id', '=', $this->user_id)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],

            'social_site_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = SocialSite::where('id', $value)
                        ->where('social_sites.is_active', 1)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
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


            'profile_link' => "nullable|string",

        ];
    }
}
