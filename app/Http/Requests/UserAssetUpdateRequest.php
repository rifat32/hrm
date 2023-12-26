<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\UserAsset;
use Illuminate\Foundation\Http\FormRequest;

class UserAssetUpdateRequest extends FormRequest
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
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = UserAsset::where('id', $value)
                        ->where('user_assets.user_id', '=', $this->user_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'name' => "required|string",
            'code' => "required|string",
            "is_working" => "required|boolean",
            'serial_number' => "required|string",
            'type' => "required|string",
            'image' => "nullable|string",
            'date' => "required|date",
            'note' => "required|string",
        ];
    }
}
