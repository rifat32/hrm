<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
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
            "id" => "required|numeric",
            'note' => 'nullable|string',
            'employee_id' => 'required|numeric',
            'in_time' => 'required|date_format:H:i:s',
            'out_time' => 'nullable|date_format:H:i:s|after_or_equal:in_time',
            'in_date' => 'required|date',
            'does_break_taken' => "required|boolean"
        ];
    }
}
