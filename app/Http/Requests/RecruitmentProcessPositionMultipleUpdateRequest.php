<?php

namespace App\Http\Requests;

use App\Models\RecruitmentProcess;
use Illuminate\Foundation\Http\FormRequest;

class RecruitmentProcessPositionMultipleUpdateRequest extends FormRequest
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
            'recruitment_processes' => 'present|array',
            'recruitment_processes.*.id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $recruitment_process_query_params = [
                        "id" => $value,
                    ];
                    $recruitment_process = RecruitmentProcess::where($recruitment_process_query_params)
                        ->first();

                    if (!$recruitment_process) {
                        $fail("No recruitment process found for $attribute.");
                    }

                    // Additional role-based permission checks can be added here if needed.
                },
            ],
            'recruitment_processes.*.employee_order_no' => 'required|numeric',
            'recruitment_processes.*.candidate_order_no' => 'required|numeric',
        ];
    }
}
