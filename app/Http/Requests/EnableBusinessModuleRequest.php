<?php

namespace App\Http\Requests;

use App\Rules\ValidateModuleIds;
use Illuminate\Foundation\Http\FormRequest;

class EnableBusinessModuleRequest extends FormRequest
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
            'business_id' => 'required|numeric|exists:businesses,id',
            "active_module_ids" => "present|array",
            "active_module_ids.*" => [
                "numeric",
                new ValidateModuleIds()
            ],
        ];
    }
}
