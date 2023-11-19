<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentUpdateRequest extends FormRequest
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
            'name' => 'required|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'manager_id' => 'required|numeric',
            'parent_id' => 'nullable|numeric',

        ];
    }
}
