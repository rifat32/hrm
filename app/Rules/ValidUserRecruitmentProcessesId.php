<?php

namespace App\Rules;

use App\Models\UserRecruitmentProcess;
use Illuminate\Contracts\Validation\Rule;

class ValidUserRecruitmentProcessesId implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    private $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
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
        $userRecruitmentProcess = UserRecruitmentProcess::where([
            'user_recruitment_process.user_id' => $this->user_id,
            'user_recruitment_process.recruitment_process_id' => $value,
        ])
        ->first();

        return $userRecruitmentProcess?1:0;
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
