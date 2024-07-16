<?php

namespace App\Http\Requests;

use App\Rules\ValidProjectId;
use Illuminate\Foundation\Http\FormRequest;

class LabelCreateRequest extends FormRequest
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


            "name" => "required|string",
            "color" => "nullable|string",
            'project_id' => [
                "required",
                "numeric",
                new ValidProjectId()
            ],
          ];
    }
}
