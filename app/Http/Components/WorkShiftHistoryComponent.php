<?php

namespace App\Http\Components;

use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

class WorkShiftHistoryComponent
{
    public function get_work_shift_history($in_date,$user_id,$throwError=true)
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
            // @@@confusion
            ->orWhere(function($query) {
               $query->where([
                "business_id" => NULL,
                "is_active" => 1,
                "is_default" => 1
               ]);
            })
            ->orderByDesc("work_shift_histories.id")


            ->first();
        if (!$work_shift_history) {
            if($throwError) {
                throw new Exception("Please define workshift first",401);
            } else {
                return false;
            }
        }

        return $work_shift_history;


    }
    public function get_work_shift_histories($start_date,$end_date,$user_id,$throwError)
    {
     $work_shift_histories =   WorkShiftHistory::
            where("from_date", "<=", $end_date)
            ->where(function ($query) use ($start_date) {
                $query->where("to_date", ">", $start_date)
                    ->orWhereNull("to_date");
            })
            ->whereHas("users", function ($query) use ($start_date, $user_id, $end_date) {
                $query->where("users.id", $user_id)
                    ->where("employee_user_work_shift_histories.from_date", "<", $end_date)
                    ->where(function ($query) use ($start_date) {
                        $query->where("employee_user_work_shift_histories.to_date", ">=", $start_date)
                            ->orWhereNull("employee_user_work_shift_histories.to_date");
                    });
            })

            ->get();

        if ($work_shift_histories->isEmpty()) {
            if($throwError) {
                throw new Exception("Please define workshift first",401);
            } else {
                return false;
            }
        }

        return $work_shift_histories;
    }


    public function get_work_shift_historiesV2($start_date,$end_date,$user_id,$throwError)
    {
     $work_shift_histories =   WorkShiftHistory::
            where("from_date", "<=", $end_date)
            ->where(function ($query) use ($start_date) {
                $query->where("to_date", ">", $start_date)
                    ->orWhereNull("to_date");
            })
            ->whereHas("users", function ($query) use ($start_date, $user_id, $end_date) {
                $query->where("users.id", $user_id)
                    ->where("employee_user_work_shift_histories.from_date", "<", $end_date)
                    ->where(function ($query) use ($start_date) {
                        $query->where("employee_user_work_shift_histories.to_date", ">=", $start_date)
                            ->orWhereNull("employee_user_work_shift_histories.to_date");
                    });
            })
 ->with("details")
            ->get();

        if ($work_shift_histories->isEmpty()) {
            if($throwError) {
                throw new Exception("Please define workshift first",401);
            } else {
                return false;
            }
        }

        return $work_shift_histories;
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

    public function updateWorkShiftsQuery($all_manager_department_ids,$query) {
    $query = $query->when(!empty(auth()->user()->business_id), function ($query) use ( $all_manager_department_ids) {
        return $query
        ->where(function($query) use($all_manager_department_ids) {
          return  $query->where(function($query) use($all_manager_department_ids) {
                $query
                ->where([
                    "work_shifts.business_id" => auth()->user()->business_id
                ])
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                });

            })

            ->orWhere(function($query)  {
                $query->where([
                    "is_active" => 1,
                    "business_id" => NULL,
                    "is_default" => 1
                ]) ;

            });
        });

    })

    ->when(empty(auth()->user()->business_id), function ($query)  {
        return $query->where([
            "work_shifts.is_default" => 1,
            "work_shifts.business_id" => NULL
        ]);
    })
        ->when(!empty(request()->search_key), function ($query) {
            return $query->where(function ($query) {
                $term = request()->search_key;
                $query->where("work_shifts.name", "like", "%" . $term . "%")
                    ->orWhere("work_shifts.description", "like", "%" . $term . "%");.
            });
        })


        ->when(!empty(request()->name), function ($query)  {
            $term = request()->name;
            return $query->where("work_shifts.name", "like", "%" . $term . "%");
        })
        ->when(!empty(request()->description), function ($query)  {
            $term = request()->description;
            return $query->where("work_shifts.description", "like", "%" . $term . "%");
        })

        ->when(!empty(request()->type), function ($query)  {
            return $query->where('work_shifts.type', (request()->type));
        })

        ->when(request()->boolean("is_active"), function ($query)  {
            return $query->where('work_shifts.is_active', 1);
    })

        ->when(isset(request()->is_personal), function ($query)  {
            return $query->where('work_shifts.is_personal', intval(request()->is_personal));
        })
        ->when(!isset(request()->is_personal), function ($query)  {
            return $query->where('work_shifts.is_personal', 0);
        })
        ->when(isset(request()->is_default), function ($query)  {
            return $query->where('work_shifts.is_default', intval(request()->is_personal));
        })
        //    ->when(!empty(request()->product_category_id), function ($query)  {
        //        return $query->where('product_category_id', request()->product_category_id);
        //    })
        ->when(!empty(request()->start_date), function ($query)  {
            return $query->where('work_shifts.created_at', ">=", request()->start_date);
        })

        ->when(!empty(request()->end_date), function ($query)  {
            return $query->where('work_shifts.created_at', "<=", (request()->end_date . ' 23:59:59'));
        });
        return $query;
    }





    public function getWorkShiftByUserId ($user_id) {
        $work_shift =   WorkShift::with("details")

        ->where(function($query) use($user_id) {
            $query->where([
                "business_id" => auth()->user()->business_id
            ])->whereHas('users', function ($query) use ($user_id) {
                $query->where('users.id', $user_id);
            });
        })
        ->orWhere(function($query) {
            $query->where([
                "is_active" => 1,
                "business_id" => NULL,
                "is_default" => 1
            ]);

        })

        ->first();

         if (empty($work_shift)) {
            throw new Exception("no work shift found for the user",404);
         }

         return $work_shift;

    }



}
