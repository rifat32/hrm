<?php

namespace App\Http\Requests;

use App\Models\Department;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentCreateRequest extends FormRequest
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
            'name' => 'required|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'manager_id' => 'required|numeric',
            'parent_id' => [
                "nullable",
                'numeric',
                Rule::requiredIf(function () {
                    $exists = Department::whereNull('parent_id')
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    return $exists;
                }),
            ],

        ];
    }
}
