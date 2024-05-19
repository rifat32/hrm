<?php

namespace App\Http\Requests;

use App\Rules\ValidateLabelId;
use Illuminate\Foundation\Http\FormRequest;

class LabelUpdateRequest extends FormRequest
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
          "id" => [
            'nullable',
            'numeric',
            new ValidateLabelId(),
          ],

          "name" => "required|string",
          "color" => "nullable|string",

        ];




    }
}
