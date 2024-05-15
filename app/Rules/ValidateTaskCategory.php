<?php

namespace App\Rules;

use App\Models\TaskCategory;
use Illuminate\Contracts\Validation\Rule;

class ValidateTaskCategory implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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

        $exists = TaskCategory::where("task_categories.id",$value)
        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
            if (auth()->user()->hasRole('superadmin')) {
                return $query->where('task_categories.business_id', NULL)
                    ->where('task_categories.is_default', 1)
                    ->where('task_categories.is_active', 1);

            } else {
                return $query->where('task_categories.business_id', NULL)
                    ->where('task_categories.is_default', 1)
                    ->where('task_categories.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) {
                        $q->whereIn("disabled_task_categories.created_by", [auth()->user()->id]);
                    })

                    ->orWhere(function ($query) use($value)  {
                        $query->where("task_categories.id",$value)->where('task_categories.business_id', NULL)
                            ->where('task_categories.is_default', 0)
                            ->where('task_categories.created_by', auth()->user()->id)
                            ->where('task_categories.is_active', 1);


                    });
            }
        })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                return $query->where('task_categories.business_id', NULL)
                    ->where('task_categories.is_default', 1)
                    ->where('task_categories.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                        $q->whereIn("disabled_task_categories.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function($q)  {
                        $q->whereIn("disabled_task_categories.business_id",[auth()->user()->business_id]);
                    })

                    ->orWhere(function ($query) use( $created_by, $value){
                        $query->where("task_categories.id",$value)->where('task_categories.business_id', NULL)
                            ->where('task_categories.is_default', 0)
                            ->where('task_categories.created_by', $created_by)
                            ->where('task_categories.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) {
                                $q->whereIn("disabled_task_categories.business_id",[auth()->user()->business_id]);
                            });
                    })
                    ->orWhere(function ($query) use($value)  {
                        $query->where("task_categories.id",$value)->where('task_categories.business_id', auth()->user()->business_id)
                            ->where('task_categories.is_default', 0)
                            ->where('task_categories.is_active', 1);

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
        return 'The selected :attribute is invalid.';
    }
}
