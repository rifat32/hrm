<?php

namespace App\Http\Requests;

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
            'note' => 'nullable|string',
            'employee_id' => 'required|numeric',
            'in_time' => 'required|date_format:H:i:s',
            'out_time' => 'nullable|date_format:H:i:s|after_or_equal:in_time',
            'in_date' => 'required|date',
        ];
    }
}
