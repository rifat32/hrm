<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateTerminationReasonId;
use App\Rules\ValidateTerminationTypeId;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;


class UserExitRequest extends FormRequest
{
    use BasicUtil;
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

        $all_manager_department_ids = $this->get_all_departments_of_manager();
        return [
            'id' => [
                'required',
                'numeric',
                new ValidateUser($all_manager_department_ids)
            ],
            'termination.termination_type_id' => [
                'required',
                'numeric',
                new ValidateTerminationTypeId()
            ],
            'termination.termination_reason_id' => [
                'required',
                'numeric',
                new ValidateTerminationReasonId()
            ],

            'termination.date_of_termination' => 'required|date',

            'exit_interview.exit_interview_conducted' => 'required|boolean',
            'exit_interview.date_of_exit_interview' => 'nullable|date',
            'exit_interview.interviewer_name' => 'nullable|string|max:255',
            'exit_interview.key_feedback_points' => 'nullable|string',
            'exit_interview.assets_returned' => 'required|boolean',
            'exit_interview.attachments' => 'present|array',
            'access_revocation.email_access_revoked' => 'required|boolean',
            'access_revocation.system_access_revoked_date' => 'nullable|date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'id.required' => 'The ID is required.',
            'id.numeric' => 'The ID must be a number.',
            'termination.termination_type_id.required' => 'The termination type is required.',
            'termination.termination_type_id.exists' => 'The selected termination type is invalid.',
            'termination.termination_reason_id.required' => 'The termination reason is required.',
            'termination.termination_reason_id.exists' => 'The selected termination reason is invalid.',
            'termination.date_of_termination.required' => 'The date of termination is required.',
            'termination.date_of_termination.date' => 'The date of termination must be a valid date.',
            'termination.date_of_termination.after_or_equal' => 'The date of termination must be after or equal to the joining date.',

            'exit_interview.exit_interview_conducted.required' => 'The exit interview conducted field is required.',
            'exit_interview.exit_interview_conducted.boolean' => 'The exit interview conducted field must be true or false.',
            'exit_interview.date_of_exit_interview.date' => 'The date of exit interview must be a valid date.',
            'exit_interview.interviewer_name.string' => 'The interviewer name must be a string.',
            'exit_interview.interviewer_name.max' => 'The interviewer name may not be greater than 255 characters.',
            'exit_interview.key_feedback_points.string' => 'The key feedback points must be a string.',
            'exit_interview.assets_returned.required' => 'The assets returned field is required.',
            'exit_interview.assets_returned.boolean' => 'The assets returned field must be true or false.',
            'exit_interview.attachments.present' => 'The attachments field must be present.',
            'exit_interview.attachments.array' => 'The attachments field must be an array.',
            'access_revocations.email_access_revoked.required' => 'The email access revoked field is required.',
            'access_revocations.email_access_revoked.boolean' => 'The email access revoked field must be true or false.',
            'access_revocations.system_access_revoked_date.date' => 'The system access revoked date must be a valid date.',
        ];
    }


}
