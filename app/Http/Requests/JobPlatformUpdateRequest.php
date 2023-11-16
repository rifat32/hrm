<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobPlatformUpdateRequest extends FormRequest
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
        $rules = [
            'id' => 'required|numeric',
            'name' => 'required|string',
            'description' => 'nullable|string',
        ];

        if (!empty(auth()->user()->business_id)) {
            $rules['name'] .= '|unique:job_platforms,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        } else {
            $rules['name'] .= '|unique:job_platforms,name,'.$this->id.',id,is_default,' . (auth()->user()->hasRole('superadmin') ? 1 : 0);
        }

return $rules;
    }
}
