<?php

namespace App\Rules;

use App\Models\SettingLeaveType;
use Illuminate\Contracts\Validation\Rule;

class UniqueSettingLeaveTypeName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $id;
    protected $errMessage;

    public function __construct($id)
    {
        $this->id = $id;
        $this->errMessage = "";

    }
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $created_by  = NULL;
        if(auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }

        $data = SettingLeaveType::where("setting_leave_types.name",$value)
        ->when(!empty($this->id), function($query) {
            $query->whereNotIn('id', [$this->id]);
        })
        // ->where('setting_leave_types.is_active', 1)
        ->when(empty(auth()->user()->business_id), function ($query)  {

             $query->where(function($query) {
                if (auth()->user()->hasRole('superadmin')) {
                    return $query->where('setting_leave_types.business_id', NULL)
                        ->where('setting_leave_types.is_default', 1);


                } else {
                    return $query->where('setting_leave_types.business_id', NULL)
                        ->where('setting_leave_types.is_default', 1)
                        ->where('setting_leave_types.is_active', 1)
                        // ->whereDoesntHave("disabled", function($q) {
                        //     $q->whereIn("disabled_setting_leave_types.created_by", [auth()->user()->id]);
                        // })

                        ->orWhere(function ($query)   {
                            $query->where('setting_leave_types.business_id', NULL)
                                ->where('setting_leave_types.is_default', 0)
                                ->where('setting_leave_types.created_by', auth()->user()->id);


                        });
                }
            });

        })
            ->when(!empty(auth()->user()->business_id), function ($query)  {
                return $query

                ->where(function ($query)  {
                    $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                        ->where('setting_leave_types.is_default', 0)
                        // ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                        //     $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                        // })
                        // ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                        //     $q->whereIn("disabled_setting_leave_types.business_id", [auth()->user()->business_id]);
                        // })
                        ;
                });
            })
        ->first();


        if(!empty($data)){


            if ($data->is_active) {
                $this->errMessage = "A leave type with the same name already exists.";
            } else {
                $this->errMessage = "A leave type with the same name exists but is deactivated. Please activate it to use.";
            }


            return 0;

        }
     return 1;

    }

    public function message()
    {
        return $this->errMessage;
    }
}
