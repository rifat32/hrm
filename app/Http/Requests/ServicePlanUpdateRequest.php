<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServicePlanUpdateRequest extends FormRequest
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
            'set_up_amount' => 'required|numeric',
            'monthly_amount' => 'required|numeric',
            'business_tier_id' => 'required|exists:business_tiers,id',
        ];

    }
}
