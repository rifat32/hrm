<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCreateRequest extends FormRequest
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



            'employee_id' => [
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
            'note' => 'nullable|string',


            'in_time' => 'required|date_format:H:i:s',
            'out_time' => 'nullable|date_format:H:i:s|after_or_equal:in_time',



            'in_date' => 'required|date',
            'does_break_taken' => "required|boolean"
        ];
    }
}
