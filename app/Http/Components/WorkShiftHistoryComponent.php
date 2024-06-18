<?php

namespace App\Http\Components;

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

    public function updateWorkShiftsQuery($request,$all_manager_department_ids,$query) {
    $query = $query->when(!empty(auth()->user()->business_id), function ($query) use ( $all_manager_department_ids) {
        return $query
        ->where(function($query) use($all_manager_department_ids) {
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
            ])
        //     ->whereHas('details', function($query) use($business_times) {

        //     foreach($business_times as $business_time) {
        //         $query->where([
        //             "day" => $business_time->day,
        //         ]);
        //         if($business_time["is_weekend"]) {
        //             $query->where([
        //                 "is_weekend" => 1,
        //             ]);
        //         } else {
        //             $query->where(function($query) use($business_time) {
        //                 $query->whereTime("start_at", ">=", $business_time->start_at);
        //                 $query->orWhereTime("end_at", "<=", $business_time->end_at);
        //             });
        //         }

        //     }
        // })
        ;

        });
    })

    ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
        return $query->where([
            "work_shifts.is_default" => 1,
            "work_shifts.business_id" => NULL
        ]);
    })
        ->when(!empty($request->search_key), function ($query) use ($request) {
            return $query->where(function ($query) use ($request) {
                $term = $request->search_key;
                $query->where("work_shifts.name", "like", "%" . $term . "%")
                    ->orWhere("work_shifts.description", "like", "%" . $term . "%");
            });
        })





        ->when(isset($request->name), function ($query) use ($request) {
            $term = $request->name;
            return $query->where("work_shifts.name", "like", "%" . $term . "%");
        })
        ->when(isset($request->description), function ($query) use ($request) {
            $term = $request->description;
            return $query->where("work_shifts.description", "like", "%" . $term . "%");
        })

        ->when(isset($request->type), function ($query) use ($request) {
            return $query->where('work_shifts.type', ($request->type));
        })






        ->when(isset($request->is_personal), function ($query) use ($request) {
            return $query->where('work_shifts.is_personal', intval($request->is_personal));
        })
        ->when(!isset($request->is_personal), function ($query) use ($request) {
            return $query->where('work_shifts.is_personal', 0);
        })


        ->when(isset($request->is_default), function ($query) use ($request) {
            return $query->where('work_shifts.is_default', intval($request->is_personal));
        })


        //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
        //        return $query->where('product_category_id', $request->product_category_id);
        //    })
        ->when(!empty($request->start_date), function ($query) use ($request) {
            return $query->where('work_shifts.created_at', ">=", $request->start_date);
        })

        ->when(!empty($request->end_date), function ($query) use ($request) {
            return $query->where('work_shifts.created_at', "<=", ($request->end_date . ' 23:59:59'));
        });
        return $query;
    }
}
