<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SettingLeaveCreateRequest extends BaseFormRequest
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
            'start_month' => 'required|integer|min:1|max:12',
            'approval_level' => 'required|string|in:single,multiple', // Adjust the valid values as needed
            'allow_bypass' => 'required|boolean',
            'special_users' => 'present|array',
            'special_users.*' => [
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
                $fail($attribute . " is invalid.");
                return;
            }
                },

            ],


            'special_roles' => 'present|array',

            'special_roles.*' => [
                'numeric',
                function ($attribute, $value, $fail) {
                    $role = Role::where("id", $value)
                        ->first();


                    if (!$role) {
                        // $fail($attribute . " is invalid.");
                        $fail("Role does not exists.");
                    }
                    if (empty(auth()->user()->business_id)) {
                        if (!(empty($role->business_id) || $role->is_default == 1)) {
                            // $fail($attribute . " is invalid.");
                            $fail("User belongs to another business.");
                        }
                    } else {
                        if ($role->business_id != auth()->user()->business_id) {
                            // $fail($attribute . " is invalid.");
                            $fail("User belongs to another business.");
                        }
                    }
                },
            ],
            'paid_leave_employment_statuses' => 'present|array',

            'paid_leave_employment_statuses.*' => [
                'numeric',
                function ($attribute, $value, $fail) {

                    $created_by  = NULL;
                    if(auth()->user()->business) {
                        $created_by = auth()->user()->business->created_by;
                    }

                    $exists = EmploymentStatus::where("employment_statuses.id",$value)
                    ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                        if (auth()->user()->hasRole('superadmin')) {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1);

                        } else {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) {
                                    $q->whereIn("disabled_employment_statuses.created_by", [auth()->user()->id]);
                                })

                                ->orWhere(function ($query) use($value)  {
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.created_by', auth()->user()->id)
                                        ->where('employment_statuses.is_active', 1);


                                });
                        }
                    })
                        ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) use($created_by) {
                                    $q->whereIn("disabled_employment_statuses.created_by", [$created_by]);
                                })
                                ->whereDoesntHave("disabled", function($q)  {
                                    $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                })

                                ->orWhere(function ($query) use( $created_by, $value){
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.created_by', $created_by)
                                        ->where('employment_statuses.is_active', 1)
                                        ->whereDoesntHave("disabled", function($q) {
                                            $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                        });
                                })
                                ->orWhere(function ($query) use($value)  {
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', auth()->user()->business_id)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.is_active', 1);

                                });
                        })
                    ->exists();

                if (!$exists) {
                    $fail($attribute . " is invalid.");
                }

                },
            ],
            'unpaid_leave_employment_statuses' => 'present|array',
            'unpaid_leave_employment_statuses.*' => [
                'numeric',
                function ($attribute, $value, $fail) {

                    $created_by  = NULL;
                    if(auth()->user()->business) {
                        $created_by = auth()->user()->business->created_by;
                    }

                    $exists = EmploymentStatus::where("employment_statuses.id",$value)
                    ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                        if (auth()->user()->hasRole('superadmin')) {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1);

                        } else {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) {
                                    $q->whereIn("disabled_employment_statuses.created_by", [auth()->user()->id]);
                                })

                                ->orWhere(function ($query) use($value)  {
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.created_by', auth()->user()->id)
                                        ->where('employment_statuses.is_active', 1);


                                });
                        }
                    })
                        ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                            return $query->where('employment_statuses.business_id', NULL)
                                ->where('employment_statuses.is_default', 1)
                                ->where('employment_statuses.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) use($created_by) {
                                    $q->whereIn("disabled_employment_statuses.created_by", [$created_by]);
                                })
                                ->whereDoesntHave("disabled", function($q)  {
                                    $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                })

                                ->orWhere(function ($query) use( $created_by, $value){
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.created_by', $created_by)
                                        ->where('employment_statuses.is_active', 1)
                                        ->whereDoesntHave("disabled", function($q) {
                                            $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                        });
                                })
                                ->orWhere(function ($query) use($value)  {
                                    $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', auth()->user()->business_id)
                                        ->where('employment_statuses.is_default', 0)
                                        ->where('employment_statuses.is_active', 1);

                                });
                        })
                    ->exists();

                if (!$exists) {
                    $fail($attribute . " is invalid.");
                }

                },
            ],

        ];

    }
    public function messages()
    {
        return [
            'allow_bypass.in' => 'The :attribute field must be either "single" or "multiple".',
        ];
    }
}
