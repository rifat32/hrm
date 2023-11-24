<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;

class LeaveApproveRequest extends FormRequest
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
            'leave_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = Leave::where('leaves.id', $value)
                        ->where('leaves.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            "is_approved" => "required|boolean"
        ];
    }
}
