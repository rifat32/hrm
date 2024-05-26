<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\EmployeeRota;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateDuplicateRotaDepartment;
use App\Rules\ValidateDuplicateRotaUser;
use App\Rules\ValidUserId;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeRotaUpdateRequest extends FormRequest
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
                function ($attribute, $value, $fail) use($all_manager_department_ids) {
                    $work_shift = EmployeeRota::where('id', $value)
                        ->where('employee_rotas.business_id', '=', auth()->user()->business_id)
                        ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        })
                        ->first();
                    if (!$work_shift) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                    if($work_shift->is_default == 1 && $work_shift->business_id == NULL && !auth()->user()->hasRole("superadmin")){

                            $fail($attribute . " is invalid. you are not a super admin.");
                            return;

                    }
                },
            ],

            'name' => 'required|string',
            'description' => 'nullable|string',

            // 'is_personal' => 'required|boolean',

            // 'type' => 'required|string|in:regular,scheduled,flexible',



            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',




            'departments' => 'present|array',
            
            'departments.*' => [

                'numeric',

                new ValidateDepartment($all_manager_department_ids),

                new ValidateDuplicateRotaDepartment($this->id)

            ],
            'users' => 'present|array',
            'users.*' => [

                "numeric",

                new ValidUserId($all_manager_department_ids),

                new ValidateDuplicateRotaUser($this->id)

            ],




            'details' => 'required|array',
            'details.*.day' => 'required|numeric|between:0,6',
            'details.*.break_type' => 'required|string|in:paid,unpaid',
            'details.*.break_hours' => 'required|numeric',
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
            'type.in' => 'The :attribute field must be either "regular" or "scheduled or flexible".',

        ];
    }
}
