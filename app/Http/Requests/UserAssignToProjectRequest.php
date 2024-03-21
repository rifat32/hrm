<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Rules\ValidUserId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class UserAssignToProjectRequest extends BaseFormRequest
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
        $all_manager_department_ids = [];
        $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
        foreach ($manager_departments as $manager_department) {
            $all_manager_department_ids[] = $manager_department->id;
            $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
        }
        return [
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('projects')
                        ->where('id', $value)
                        ->where('projects.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],

            'users' => 'present|array',
            'users.*' => [
                "numeric",
                new ValidUserId($all_manager_department_ids),
            ],
        ];
    }
}
