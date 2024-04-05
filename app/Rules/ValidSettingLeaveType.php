<?php

namespace App\Rules;

use App\Models\Leave;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class ValidSettingLeaveType implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $user_id;
    protected $id;

    public function __construct($user_id,$id)
    {
        $this->user_id = $user_id;
        $this->id = $id;
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
$leave = Leave::where([
    "id" => $this->id,
    "leave_type_id" => $value
])
->first();

if($leave) {
return true;
}

        $user = User::where([
            "id" => $this->user_id
        ])
        ->first();


        $created_by  = NULL;
        if(auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }
        $setting_leave = SettingLeave::
        where('setting_leaves.business_id', auth()->user()->business_id)
       ->where('setting_leaves.is_default', 0)
       ->first();
       if(!$setting_leave || !$user) {
        return 0;
       }

        $paid_leave_available = in_array($user->employment_status_id, $setting_leave->paid_leave_employment_statuses()->pluck("employment_statuses.id")->toArray());

        $exists = SettingLeaveType::where("setting_leave_types.id",$value)
        ->when($paid_leave_available == 0, function($query) {
            $query->where('setting_leave_types.type', "unpaid");
        })
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
                return $query
                // ->where('setting_leave_types.business_id', NULL)
                //     ->where('setting_leave_types.is_default', 1)
                //     ->where('setting_leave_types.is_active', 1)
                //     ->whereDoesntHave("disabled", function($q) use($created_by) {
                //         $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                //     })
                //     ->whereDoesntHave("disabled", function($q)  {
                //         $q->whereIn("disabled_setting_leave_types.business_id",[auth()->user()->business_id]);
                //     })

                //     ->orWhere(function ($query) use( $created_by, $value){
                //         $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', NULL)
                //             ->where('setting_leave_types.is_default', 0)
                //             ->where('setting_leave_types.created_by', $created_by)
                //             ->where('setting_leave_types.is_active', 1)
                //             ->whereDoesntHave("disabled", function($q) {
                //                 $q->whereIn("disabled_setting_leave_types.business_id",[auth()->user()->business_id]);
                //             });
                //     })
                //     ->orWhere(function ($query) use($value)  {
                //         $query->where("setting_leave_types.id",$value)->where('setting_leave_types.business_id', auth()->user()->business_id)
                //             ->where('setting_leave_types.is_default', 0)
                //             ->where('setting_leave_types.is_active', 1);

                //     });
                ->where(function ($query) use($created_by) {
                    $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                        ->where('setting_leave_types.is_default', 0)
                        ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                            $q->whereIn("disabled_setting_leave_types.created_by", [$created_by]);
                        })
                        ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                            $q->whereIn("disabled_setting_leave_types.business_id", [auth()->user()->business_id]);
                        })
                        ;
                });
            })
        ->exists();

         return $exists;
    }





    /**
     * Get the validation error message.
     *
     * @return string
     */



    public function message()
    {
        return 'The :attribute is invalid.';
    }
}
