<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReminderCreateRequest extends FormRequest
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
            'title' => 'required|string',
            'duration' => 'required|numeric',
            'duration_unit' => 'required|in:days,weeks,months',
            'send_time' => 'required|in:before_expiry,after_expiry',
            'frequency_after_first_reminder' => 'required:send_time,after_expiry|integer',
            'keep_sending_until_update' => 'required|boolean',
            'entity_name' => 'required|string',

        ];
    }
    public function messages()
    {
        return [
            'duration_unit.in' => 'The :attribute must be either "before_expiry" or "after_expiry ".',
        ];
    }
}
