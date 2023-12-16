<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class WorkShiftCreateRequest extends FormRequest
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
            'description' => 'nullable|string',
            'type' => 'required|string|in:regular,scheduled',


            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'departments' => 'present|array',
            'departments.*' => [
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                },
            ],
            'users' => 'present|array',
            'users.*' => [
                "numeric",
                function ($attribute, $value, $fail) {
                    $user = User::where("id", $value)->get();

                        if (!$user){
                            // $fail("$attribute is invalid.");
                            $fail("Employee does not exists.");

                        }

                        if ($user->business_id != auth()->user()->business_id) {
                            // $fail("$attribute is invalid.");
                            $fail("Employee belongs to another business.");

                        }

                        if (!$user->hasRole(("business_owner" . "#" . auth()->user()->business_id)) && !$user->hasRole(("business_admin" . "#" . auth()->user()->business_id))  && !$user->hasRole(("business_manager" . "#" . auth()->user()->business_id)) &&  !$user->hasRole(("business_employee" . "#" . auth()->user()->business_id))){
                            // $fail("$attribute is invalid.");
                            $fail("The user is not a employee");

                        }


                    return [
                        "ok" => true,
                    ];
                },

            ],
            'details' => 'required|array|min:7|max:7',
            'details.*.day' => 'required|numeric|between:0,6',
            'details.*.is_weekend' => 'required|boolean',
            'details.*.start_at' => [
                'nullable',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $isWeekend = request('details')[$index]['is_weekend'] ?? false;

                    if (request('type') === 'scheduled' && $isWeekend == 0 && empty($value)) {
                        $fail("The $attribute field is required when type is scheduled and is_weekend is 0.");
                    }
                },
            ],
            'details.*.end_at' => [
                'nullable',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $isWeekend = request('details')[$index]['is_weekend'] ?? false;

                    if (request('type') === 'scheduled' && $isWeekend == 0 && empty($value)) {
                        $fail("The $attribute field is required when type is scheduled and is_weekend is 0.");
                    }
                },
            ],


        ];
    }
    public function messages()
{
    return [
        'type.in' => 'The :attribute field must be either "regular" or "scheduled".',
    ];
}
}
