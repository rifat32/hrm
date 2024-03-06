<?php

namespace App\Rules;

use App\Models\Project;
use Illuminate\Contracts\Validation\Rule;

class ValidProjectId implements Rule
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

 
    public function passes($attribute, $value)
    {
        return Project::where('id', $value)
            ->where('projects.business_id', '=', auth()->user()->business_id)
            ->exists();
    }

    public function message()
    {
        return 'The :attribute is invalid.';
    }
}
