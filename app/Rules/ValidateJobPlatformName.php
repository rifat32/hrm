<?php

namespace App\Rules;

use App\Models\JobPlatform;
use Illuminate\Contracts\Validation\Rule;

class ValidateJobPlatformName implements Rule
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

        $exists = JobPlatform::where("job_platforms.name",$value)

        ->when(!empty($this->id),function($query) {
            $query->whereNotIn("id",[$this->id]);
        })

        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
            if (auth()->user()->hasRole('superadmin')) {
                return $query->where('job_platforms.business_id', NULL)
                    ->where('job_platforms.is_default', 1)
                    ->where('job_platforms.is_active', 1);

            } else {
                return $query->where('job_platforms.business_id', NULL)
                    ->where('job_platforms.is_default', 1)
                    ->where('job_platforms.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) {
                        $q->whereIn("disabled_job_platforms.created_by", [auth()->user()->id]);
                    })

                    ->orWhere(function ($query) use($value)  {
                        $query->where("job_platforms.id",$value)->where('job_platforms.business_id', NULL)
                            ->where('job_platforms.is_default', 0)
                            ->where('job_platforms.created_by', auth()->user()->id)
                            ->where('job_platforms.is_active', 1);


                    });
            }
        })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                return $query->where('job_platforms.business_id', NULL)
                    ->where('job_platforms.is_default', 1)
                    ->where('job_platforms.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                        $q->whereIn("disabled_job_platforms.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function($q)  {
                        $q->whereIn("disabled_job_platforms.business_id",[auth()->user()->business_id]);
                    })
                    ->orWhere(function ($query) use( $created_by, $value){
                        $query->where('job_platforms.business_id', NULL)
                            ->where('job_platforms.is_default', 0)
                            ->where('job_platforms.created_by', $created_by)
                            ->where('job_platforms.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) {
                                $q->whereIn("disabled_job_platforms.business_id",[auth()->user()->business_id]);
                            });
                    })
                    ->orWhere(function ($query) use($value)  {
                        $query->where('job_platforms.business_id', auth()->user()->business_id)
                            ->where('job_platforms.is_default', 0)
                            ->where('job_platforms.is_active', 1);

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
        return 'The :attribute is already taken.';
    }
}
