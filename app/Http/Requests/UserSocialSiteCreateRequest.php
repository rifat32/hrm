<?php

namespace App\Http\Requests;

use App\Models\SocialSite;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserSocialSiteCreateRequest extends FormRequest
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
        return [
            'social_site_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = SocialSite::where('id', $value)
                        ->where('social_sites.is_active',1)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'user_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = User::where('id', $value)
                        ->where('users.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'profile_link' => "required|string",
        ];
    }
}
