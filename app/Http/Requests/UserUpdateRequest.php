<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends BaseFormRequest
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
            'id' => "required|numeric",
            'first_Name' => 'required|string|max:255',
        'middle_Name' => 'nullable|string|max:255',

        'last_Name' => 'required|string|max:255',


        // 'email' => 'required|string|email|indisposable|max:255|unique:users',
        'email' => 'required|string|unique:users,email,' . $this->id . ',id',

        'password' => 'nullable|string|min:6',
        'phone' => 'nullable|string',
        'image' => 'nullable|string',
        'address_line_1' => 'required|string',
        'address_line_2' => 'nullable',
        'country' => 'required|string',
        'city' => 'required|string',
        'postcode' => 'nullable|string',
        'lat' => 'nullable|string',
        'long' => 'nullable|string',
        'role' => [
            "required",
            'string',
            function ($attribute, $value, $fail) {
                $role  = Role::where(["name" => $value])->first();


                if (!$role){
                         // $fail("$attribute is invalid.")
                         $fail("Role does not exists.");

                }

                if(!empty(auth()->user()->business_id)) {
                    if (empty($role->business_id)){
                        // $fail("$attribute is invalid.")
                      $fail("You don't have this role");

                  }
                    if ($role->business_id != auth()->user()->business_id){
                          // $fail("$attribute is invalid.")
                        $fail("You don't have this role");

                    }
                } else {
                    if (!empty($role->business_id)){
                        // $fail("$attribute is invalid.")
                      $fail("You don't have this role");

                  }
                }


            },
        ],







        'gender' => 'nullable|string|in:male,female,other',
        'is_in_employee' => "nullable|boolean",
        'designation_id' => [
            "nullable",
            'numeric',
            function ($attribute, $value, $fail) {
                if(!empty($value)){
                    $created_by  = NULL;
                    if(auth()->user()->business) {
                        $created_by = auth()->user()->business->created_by;
                    }

                    $exists = Designation::where("designations.id",$value)
                    ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                        if (auth()->user()->hasRole('superadmin')) {
                            return $query->where('designations.business_id', NULL)
                                ->where('designations.is_default', 1)
                                ->where('designations.is_active', 1);

                        } else {
                            return $query->where('designations.business_id', NULL)
                                ->where('designations.is_default', 1)
                                ->where('designations.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) {
                                    $q->whereIn("disabled_designations.created_by", [auth()->user()->id]);
                                })

                                ->orWhere(function ($query) use($value)  {
                                    $query->where("designations.id",$value)->where('designations.business_id', NULL)
                                        ->where('designations.is_default', 0)
                                        ->where('designations.created_by', auth()->user()->id)
                                        ->where('designations.is_active', 1);


                                });
                        }
                    })
                        ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                            return $query->where('designations.business_id', NULL)
                                ->where('designations.is_default', 1)
                                ->where('designations.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) use($created_by) {
                                    $q->whereIn("disabled_designations.created_by", [$created_by]);
                                })
                                ->whereDoesntHave("disabled", function($q)  {
                                    $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
                                })

                                ->orWhere(function ($query) use( $created_by, $value){
                                    $query->where("designations.id",$value)->where('designations.business_id', NULL)
                                        ->where('designations.is_default', 0)
                                        ->where('designations.created_by', $created_by)
                                        ->where('designations.is_active', 1)
                                        ->whereDoesntHave("disabled", function($q) {
                                            $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
                                        });
                                })
                                ->orWhere(function ($query) use($value)  {
                                    $query->where("designations.id",$value)->where('designations.business_id', auth()->user()->business_id)
                                        ->where('designations.is_default', 0)
                                        ->where('designations.is_active', 1);

                                });
                        })
                    ->exists();

                if (!$exists) {
                    $fail("$attribute is invalid.");
                }
                }

            },
        ],

        'joining_date' => "nullable|date",
        'salary_per_annum' => "nullable|numeric",
        'weekly_contractual_hours' => 'nullable|numeric',
        "minimum_working_days_per_week" => 'nullable|numeric|max:7',
        "overtime_rate" => 'nullable|numeric',
        ];
    }

    public function messages()
    {
        return [
            'gender.in' => 'The :attribute field must be in "male","female","other".',
        ];
    }
}
