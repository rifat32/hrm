<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Http\FormRequest;

class UserDocumentUpdateRequest extends FormRequest
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
                    $exists = UserDocument::where('id', $value)
                        ->where('user_documents.user_id', '=', $this->user_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'name' => 'required|string',
            'file_name' => 'required|string',


        ];
    }
}
