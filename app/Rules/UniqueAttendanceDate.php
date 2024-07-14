<?php

namespace App\Rules;

use App\Models\Attendance;
use App\Models\Termination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class UniqueAttendanceDate implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $id;
    protected $user_id;
    protected $failureMessage;

    public function __construct($id, $user_id)
    {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->failureMessage = '';
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
        $user = User::where([
            "id" => $this->user_id
           ])
           ->select("id","joining_date")
           ->first();


           if(empty($user)) {
            $this->failureMessage = 'No user found.';
            return 0;
           }




           if(empty($user->joining_date)){
            $this->failureMessage = 'No employee found.';
            return 0;
           }

           $joining_date = Carbon::parse($user->joining_date);

          $in_date = Carbon::parse($value);






     $exists = Attendance::when(!empty($this->id), function($query) {
            $query->whereNotIn('id', [$this->id]);
        })
            ->where('attendances.user_id', $this->user_id)
            ->where('attendances.in_date', $value)
            ->where('attendances.business_id', auth()->user()->business_id)
            ->exists();

            if($exists) {
                $this->failureMessage = 'Attendance already exists on this date.';
                return 0;
            }




            $termination =  Termination::where([
                "user_id" => $this->user_id
               ])

               ->where("date_of_termination", "<", $value)
               ->latest()
               ->first();


               if (empty($termination)) {

                if($joining_date->gt($in_date)) {
                    $this->failureMessage = 'Employee joined later after this date.';
                    return 0;
                }

            } else {
                $last_termination_date = Carbon::parse($termination->date_of_termination);
                $last_joining_date =  Carbon::parse($termination->joining_date);

                if(

            !$last_termination_date->lt($user->joining_date)
                ||
            !($last_joining_date->lte($in_date) && $last_joining_date->gte($in_date))

                )
                {
                    $this->failureMessage = 'User was termination date and attendance date mismatch.';
                    return 0;
                }

            }




































            // $terminations =  Termination::where([
            //     "user_id" => $this->user_id
            //    ])

            //    ->where("date_of_termination", "<", $value)
            //    ->orderByDesc("id")
            //    ->get();


            //    if (empty($terminations)) {

            //     if($joining_date->gt($in_date)) {
            //         $this->failureMessage = 'Employee joined later after this date.';
            //         return 0;
            //     }

            // } else {
            //     $last_termination_date = Carbon::parse($terminations[0]->date_of_termination);
            //     if(!$last_termination_date->lt($user->joining_date)
            //     ||
            //     !$terminations->first(function ($item) use($in_date){
            //       $date_of_termination =  Carbon::parse($item->date_of_termination);
            //       $joining_date =  Carbon::parse($item->joining_date);
            //   return   $joining_date->lte($in_date) &&  $date_of_termination->gte($in_date);
            //     })
            //     ){
            //         $this->failureMessage = 'User was termination date and attendance date mismatch.';
            //         return 0;
            //     }

            // }




    }

    public function message()
    {
        return $this->failureMessage;
    }
}
