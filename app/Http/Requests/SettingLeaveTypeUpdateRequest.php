<?php

namespace App\Http\Requests;

use App\Models\SettingLeaveType;
use Illuminate\Foundation\Http\FormRequest;

class SettingLeaveTypeUpdateRequest extends BaseFormRequest
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
        $rules = [
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $setting_leave_type_query_params = [
                        "id" => $this->id,
                    ];
                    $setting_leave_type = SettingLeaveType::where($setting_leave_type_query_params)
                        ->first();
                    if (!$setting_leave_type) {
                            // $fail("$attribute is invalid.");
                            $fail("no leave type found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($setting_leave_type->business_id != NULL || $setting_leave_type->is_default != 1)) {
                                // $fail("$attribute is invalid.");
                                $fail("You do not have permission to update this leave type due to role restrictions.");

                          }

                        } else {
                            if(($setting_leave_type->business_id != NULL || $setting_leave_type->is_default != 0 || $setting_leave_type->created_by != auth()->user()->id)) {
                                // $fail("$attribute is invalid.");
                                $fail("You do not have permission to update this leave type due to role restrictions.");

                          }
                        }

                    } else {
                        if(($setting_leave_type->business_id != auth()->user()->business_id || $setting_leave_type->is_default != 0)) {
                               // $fail("$attribute is invalid.");
                            $fail("You do not have permission to update this leave type status due to role restrictions.");
                        }
                    }




                },
            ],
            'name' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = SettingLeaveType::where("setting_leave_types.name",$value)
                        ->whereNotIn("id",[$this->id])

                        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                            if (auth()->user()->hasRole('superadmin')) {
                                return $query->where('setting_leave_types.business_id', NULL)
                                    ->where('setting_leave_types.is_default', 1)
                                    ->where('setting_leave_types.is_active', 1);

                            } else {
                                return $query->where('setting_leave_types.business_id', NULL)
                                    ->where('setting_leave_types.is_default', 1)
                                    ->where('setting_leave_types.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_setting_leave_types.created_by", [auth()->user()->id]);
                                    })

                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', NULL)
                                            ->where('setting_leave_types.is_default', 0)
                                            ->where('setting_leave_types.created_by', auth()->user()->id)
                                            ->where('setting_leave_types.is_active', 1);


                                    });
                            }
                        })
                            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                                return $query->where('setting_leave_types.business_id', NULL)
                                    ->where('setting_leave_types.is_default', 1)
                                    ->where('setting_leave_types.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                                        $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                                    })
                                    ->whereDoesntHave("disabled", function($q)  {
                                        $q->whereIn("disabled_setting_leave_types.business_id",[auth()->user()->business_id]);
                                    })

                                    ->orWhere(function ($query) use( $created_by, $value){
                                        $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', NULL)
                                            ->where('setting_leave_types.is_default', 0)
                                            ->where('setting_leave_types.created_by', $created_by)
                                            ->where('setting_leave_types.is_active', 1)
                                            ->whereDoesntHave("disabled", function($q) {
                                                $q->whereIn("disabled_setting_leave_types.business_id",[auth()->user()->business_id]);
                                            });
                                    })
                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', auth()->user()->business_id)
                                            ->where('setting_leave_types.is_default', 0)
                                            ->where('setting_leave_types.is_active', 1);

                                    });
                            })
                        ->exists();

                    if ($exists) {
                        $fail("$attribute is already exist.");
                    }


                },
            ],
            'type' => 'required|string|in:paid,unpaid',
            'amount' => 'required|numeric'
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:setting_leave_types,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     if(auth()->user()->hasRole('superadmin')){
        //         $rules['name'] .= '|unique:setting_leave_types,name,'.$this->id.',id,is_default,' . 1 . ',business_id,' . NULL;
        //     }
        //     else {
        //         $rules['name'] .= '|unique:setting_leave_types,name,'.$this->id.',id,is_default,' . 0 . ',business_id,' . NULL;
        //     }

        // }

        return $rules;
    }
    public function messages()
    {
        return [
            'type.in' => 'The :attribute field must be either "paid" or "unpaid".',
        ];
    }
}
