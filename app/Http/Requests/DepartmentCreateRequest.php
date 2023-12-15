<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentCreateRequest extends FormRequest
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
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'manager_id' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    if(!empty($value)){
                    $user  = User::where(["id" => $value])->first();
                    if (!$user){
                        // $fail("$attribute is invalid.");
                        $fail("Manager does not exists.");

                    }

                    if ($user->business_id != auth()->user()->business_id) {
                         // $fail("$attribute is invalid.");
                         $fail("Manager belongs to another business.");

                    }
                    if (!($user->hasRole(("business_admin" . "#" . auth()->user()->business_id)) || $user->hasRole(("business_manager" . "#" . auth()->user()->business_id)))){
                         // $fail("$attribute is invalid.");
                         $fail("Manager belongs to another business.");
                    }

                }
                },
            ],

            'parent_id' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    if(!empty($value)){
                        $exists = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail("$attribute is invalid.");
                    }
                    }

                },
            ],

        ];
    }
}
