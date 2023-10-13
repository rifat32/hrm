<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class TimeOrderRule implements Rule
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
        // // Check if 'opening_time' and 'closing_time' are valid times
        // $openingTime = strtotime($value->opening_time);
        // $closingTime = strtotime($value->closing_time);

        // // Check if opening time is less than closing time
        // return $openingTime < $closingTime;
      return  true;
    }

     public function message()
     {
         return 'The opening time must be before the closing time.';
     }
}
