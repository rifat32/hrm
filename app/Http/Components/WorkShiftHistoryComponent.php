<?php

namespace App\Http\Components;

use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

class WorkShiftHistoryComponent
{
    public function get_work_shift_history($in_date,$user_id)
    {
        $work_shift_history =  WorkShiftHistory::
           where(function($query) use($in_date,$user_id) {
          $query ->where("from_date", "<=", $in_date)
          ->where(function ($query) use ($in_date) {
              $query->where("to_date", ">", $in_date)
                  ->orWhereNull("to_date");
          })

          ->whereHas("users", function ($query) use ($in_date, $user_id) {
              $query->where("users.id", $user_id)
                  ->where("employee_user_work_shift_histories.from_date", "<=", $in_date)
                  ->where(function ($query) use ($in_date) {
                      $query->where("employee_user_work_shift_histories.to_date", ">", $in_date)
                          ->orWhereNull("employee_user_work_shift_histories.to_date");
                  });
          });
            })
            ->orWhere(function($query) {
               $query->where([
                "business_id" => NULL,
                "is_active" => 1,
                "is_default" => 1
               ]);
            })


            ->first();
        if (!$work_shift_history) {
            throw new Exception("Please define workshift first");
        }

        return $work_shift_history;
    }

    public function get_work_shift_details($work_shift_history,$in_date)
    {
        $day_number = Carbon::parse($in_date)->dayOfWeek;
        $work_shift_details =  $work_shift_history->details()->where([
            "day" => $day_number
        ])
        ->first();

        if (!$work_shift_details) {
            throw new Exception(("No work shift details found  day " . $day_number), 400);
        }
        // if ($work_shift_details->is_weekend && !auth()->user()->hasRole("business_owner")) {
        //     throw new Exception(("there is a weekend on date " . $in_date), 400);
        // }
        return $work_shift_details;
    }
}
