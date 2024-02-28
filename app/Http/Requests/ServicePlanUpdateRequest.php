<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServicePlanUpdateRequest extends BaseFormRequest
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
            "id" => "required|numeric|exists:service_plans,id",
            "name" => "required|string",
            "description" => "nullable|string",
            'set_up_amount' => 'required|numeric',
            'duration_months' => 'required|numeric',
            'price' => 'required|numeric',
            'business_tier_id' => 'required|exists:business_tiers,id',
        ];

    }
}
