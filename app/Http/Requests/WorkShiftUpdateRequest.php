<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Http\FormRequest;

class WorkShiftUpdateRequest extends BaseFormRequest
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
                    $work_shift = WorkShift::where('id', $value)
                        ->where('work_shifts.business_id', '=', auth()->user()->business_id)
                        ->first();

                    if (!$work_shift) {
                        $fail("$attribute is invalid.");
                        return;
                    }
                    if($work_shift->is_default == 1 && $work_shift->business_id == NULL && !auth()->user()->hasRole("superadmin")){

                            $fail("$attribute is invalid. you are not a super admin.");
                            return;

                    }

                },
            ],

            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_personal' => 'required|boolean',

            'break_type' => 'required|string|in:paid,unpaid',
            'break_hours' => 'required|numeric',


            'type' => 'required|string|in:regular,scheduled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'departments' => 'present|array',
            'departments.*' => [
                'numeric',
                function ($attribute, $value, $fail) use($all_manager_department_ids) {

                    $department = Department::where('id', $value)
                        ->where('departments.business_id', '=', auth()->user()->business_id)
                        ->first();

                        if (!$department) {
                            $fail("$attribute is invalid.");
                            return;
                        }
                        if(!in_array($department->id,$all_manager_department_ids)){
                            $fail("$attribute is invalid. You don't have access to this department.");
                            return;
                        }
                },
            ],
            'users' => 'present|array',
            'users.*' => [
                "numeric",
                function ($attribute, $value, $fail) use($all_manager_department_ids) {


                  $exists =  User::where(
                    [
                        "users.id" => $value,
                        "users.business_id" => auth()->user()->business_id

                    ])
                    ->whereHas("departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids);
                     })
                     ->first();

            if (!$exists) {
                $fail("$attribute is invalid.");
                return;
            }



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
            'break_type.in' => 'The :attribute field must be either "paid" or "unpaid".',
        ];
    }
}
