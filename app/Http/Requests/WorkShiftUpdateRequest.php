<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkShiftUpdateRequest extends FormRequest
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
            'id' => 'required|numeric',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|string|in:regular,scheduled',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'departments' => 'nullable|array',
            'departments.*' => 'numeric',
            'users' => 'nullable|array',
            'users.*' => 'numeric',
            'details' => 'required|array|min:7|max:7',
            'details.*.off_day' => 'required|boolean',
            'details.*.start_at' => 'required_if:type,scheduled|date',
            'details.*.end_at' => 'required_if:type,scheduled|date',
            'details.*.is_weekend' => 'required|boolean',
        ];
    }
}
