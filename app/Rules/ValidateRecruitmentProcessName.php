<?php

namespace App\Rules;

use App\Models\RecruitmentProcess;
use Illuminate\Contracts\Validation\Rule;

class ValidateRecruitmentProcessName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

     protected $id;
    public function __construct($id)
    {
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
        $created_by  = NULL;
        if(auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }

        $exists = RecruitmentProcess::where("recruitment_processes.name",$value)
        ->when(!empty($this->id),function($query) {
            $query->whereNotIn("id",[$this->id]);
        })
        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
            if (auth()->user()->hasRole('superadmin')) {
                return $query->where('recruitment_processes.business_id', NULL)
                    ->where('recruitment_processes.is_default', 1)
                    ->where('recruitment_processes.is_active', 1);

            } else {
                return $query->where('recruitment_processes.business_id', NULL)
                    ->where('recruitment_processes.is_default', 1)
                    ->where('recruitment_processes.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) {
                        $q->whereIn("disabled_recruitment_processes.created_by", [auth()->user()->id]);
                    })

                    ->orWhere(function ($query) use($value)  {
                        $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 0)
                            ->where('recruitment_processes.created_by', auth()->user()->id)
                            ->where('recruitment_processes.is_active', 1);


                    });
            }
        })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                return $query->where('recruitment_processes.business_id', NULL)
                    ->where('recruitment_processes.is_default', 1)
                    ->where('recruitment_processes.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                        $q->whereIn("disabled_recruitment_processes.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function($q)  {
                        $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                    })

                    ->orWhere(function ($query) use( $created_by, $value){
                        $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 0)
                            ->where('recruitment_processes.created_by', $created_by)
                            ->where('recruitment_processes.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) {
                                $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                            });
                    })
                    ->orWhere(function ($query) use($value)  {
                        $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', auth()->user()->business_id)
                            ->where('recruitment_processes.is_default', 0)
                            ->where('recruitment_processes.is_active', 1);

                    });
            })
        ->exists();
        return !$exists;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is already taken.';
    }
}
