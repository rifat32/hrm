<?php

namespace App\Http\Utils;

use App\Models\Attendance;
use App\Models\AttendanceArrear;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\LeaveRecordArrear;
use App\Models\Payroll;
use App\Models\PayrollAttendance;
use App\Models\PayrollLeaveRecord;
use App\Models\Payrun;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

trait PayrunUtil
{
    // this function do all the task and returns transaction id or -1
    public function process_payrun($payrun, $employees, $start_date, $end_date = NULL,$is_manual=false,$generate_payroll = false)
    {

        if (!$payrun->business_id) {
            return false;
        }

        // $end_date = $payrun->end_date;

        // Set end_date based on period_type
        if (!$start_date || $end_date) {
            switch ($payrun->period_type) {
                case 'weekly':
                    if (!$start_date) {
                        $start_date = Carbon::now()->startOfWeek()->subWeek(1);
                    }
                    if (!$end_date) {
                        $end_date = Carbon::now()->startOfWeek();
                    }
                    break;
                case 'monthly':
                    if (!$start_date) {
                        $start_date = Carbon::now()->startOfMonth()->addMonth(1);
                    }
                    if (!$end_date) {
                        $end_date = Carbon::now()->startOfMonth();
                    }
                    break;
                default:
                    if (!$start_date) {
                        $start_date = $payrun->start_date;
                    }
                    if (!$end_date) {
                        $end_date = $payrun->end_date;
                    }
                    break;
            }
        }


        if (!$start_date || !$end_date) {
            return false; // Skip to the next iteration
        }

        // Convert end_date to Carbon instance
        $end_date = Carbon::parse($end_date);

        // Check if end_date is today
        if (!$end_date->isToday() && $is_manual==false) {
            return false; // Skip to the next iteration
        }




        $employees->each(function ($employee) use ($payrun, $generate_payroll, $start_date) {

            $employee->payroll = $this->generate_payroll($payrun, $employee, $start_date, $generate_payroll);

            return $employee;
        });

        return $employees;
    }

    public function generate_payroll($payrun, $employee, $start_date, $generate_payroll)
    {


        $work_shift =   WorkShift::whereHas('users', function ($query) use ($employee) {
            $query->where('users.id', $employee->id);
        })->first();

        if (!$work_shift) {
            return [
                "message" => "no work shift found for this employee"
            ];
        }

        if (!$work_shift->is_active) {
            return [
                "message" => "work shift is not active for this employee"
            ];
        }
        $work_shift_details = $work_shift->details()->get()->keyBy('day');


        $all_parent_department_ids = [];
        $assigned_departments = Department::whereHas("users", function ($query) use ($employee) {
            $query->where("users.id", $employee->id);
        })->get();
        foreach ($assigned_departments as $assigned_department) {
            $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
        }
        $salary_per_annum = $employee->salary_per_annum; // in euros
        $weekly_contractual_hours = $employee->weekly_contractual_hours;
        $weeks_per_year = 52;
        $hourly_salary = $salary_per_annum / ($weeks_per_year * $weekly_contractual_hours);
        $overtime_salary = $employee->overtime_rate ? $employee->overtime_rate : $hourly_salary;

        $holiday_hours = $employee->weekly_contractual_hours / $employee->minimum_working_days_per_week;


        $attendance_arrears = Attendance::whereDoesntHave("payroll_attendance")
            ->where('attendances.user_id', $employee->id)

            ->where(function ($query) use ($start_date) {
                $query->where(function ($query) use ($start_date) {
                    $query->whereNotIn("attendances.status", ["approved"])
                        ->where('attendances.in_date', '<=', today()->endOfDay())
                        ->where('attendances.in_date', '>=', $start_date);
                })
                    ->orWhere(function ($query) use ($start_date) {
                        $query->whereDoesntHave("arrear")
                            ->where('attendances.in_date', '<=', $start_date);
                    });
            })
            ->get();

        $leave_arrears = LeaveRecord::whereDoesntHave("payroll_leave_record")
            ->whereHas('leave',    function ($query) use ($employee) {
                $query->where("leaves.user_id",  $employee->id);
            })

            ->where(function ($query) use ($start_date) {
                $query->where(function ($query) use ($start_date) {
                    $query
                        ->whereHas('leave',    function ($query) {
                            $query->whereNotIn("leaves.status", ["approved"]);
                        })
                        ->where('leave_records.date', '<=', today()->endOfDay())
                        ->where('leave_records.date', '>=', $start_date);
                })
                    ->orWhere(function ($query) use ($start_date) {
                        $query->whereDoesntHave("arrear")
                            ->where('leave_records.date', '<=', $start_date);
                    });
            })
            ->get();


        $approved_attendances = Attendance::whereDoesntHave("payroll_attendance")
            ->where('attendances.user_id', $employee->id)
            ->where("attendances.status", "approved")
            ->where(function ($query) use ($start_date) {
                $query
                    ->where(function ($query) use ($start_date) {
                        $query
                            ->where('attendances.in_date', '<=', today()->endOfDay())
                            ->where('attendances.in_date', '>=', $start_date);
                    })
                    ->orWhere(function ($query) {
                        $query->whereHas("arrear", function ($query) {
                            $query->where("attendance_arrears.status", "approved");
                        });
                    });
            })
            ->get();



        $approved_leave_records = LeaveRecord::whereDoesntHave("payroll_leave_record")
            ->whereHas('leave',    function ($query) use ($employee) {
                $query->where("leaves.user_id",  $employee->id)
                    ->where("leaves.status", "approved");
            })

            ->where(function ($query) use ($start_date) {
                $query->where(function ($query) use ($start_date) {
                    $query

                        ->where('leave_records.date', '<=', today()->endOfDay())
                        ->where('leave_records.date', '>=', $start_date);
                })
                    ->orWhere(function ($query) {
                        $query->whereHas("arrear", function ($query) {
                            $query->where("leave_record_arrears.status", "approved");
                        });
                    });
            })
            ->get();





        $holidays = Holiday::where([
            "business_id" => auth()->user()->business_id
        ])
            ->where('holidays.start_date', '<=', today()->endOfDay())
            ->where('holidays.end_date', '>=', $start_date)
            ->where([
                "is_active" => 1
            ])

            ->where(function ($query) use ($employee, $all_parent_department_ids) {
                $query->whereHas("users", function ($query) use ($employee) {
                    $query->where([
                        "users.id" => $employee->id
                    ]);
                })
                    ->orWhereHas("departments", function ($query) use ($all_parent_department_ids) {
                        $query->whereIn("departments.id", $all_parent_department_ids);
                    })

                    ->orWhere(function ($query) {
                        $query->whereDoesntHave("users")
                            ->whereDoesntHave("departments");
                    });
            })

            ->get();




        // $total_holiday_hours = 0;
        // $total_paid_leave_hours = 0;
        // $total_regular_attendance_hours = 0;
        // $total_overtime_attendance_hours = 0;

        $payroll_attendances_data = collect();
        $payroll_leave_records_data = collect();
        $payroll_holidays_data = collect();


        $approved_leave_records->each(function ($approved_leave_record) use (&$payroll_leave_records_data) {


            if ($approved_leave_record->leave->leave_type->type == "paid") {
                // $total_paid_leave_hours += $approved_leave_record->leave_hours;
                $payroll_leave_records_data->push([
                    "leave_record_id" => $approved_leave_record->id,
                    // "date" => $approved_leave_record->date,
                    // "start_time" => $approved_leave_record->start_time,
                    // "end_time" => $approved_leave_record->end_time,
                    // "leave_hours" => $approved_leave_record->leave_hours,
                ]);

            }

        });



        $date_range = collect();
        $holidays->each(function ($holiday) use (&$date_range, &$payroll_holidays_data, &$holiday_hours ) {
            $holiday_start_date = Carbon::parse($holiday->start_date);
            $holiday_end_date = Carbon::parse($holiday->end_date);

            while ($holiday_start_date->lte($holiday_end_date)) {
                $current_date = $holiday_start_date->format("Y-m-d");
                // Check if the date is not already in the collection before adding
                if (!$date_range->contains($current_date)) {
                    $date_range->push($current_date);
                    if (Carbon::parse($current_date)->between(today()->endOfDay(), $holiday_start_date)) {
                        $payroll_holidays_data->push([
                            "holiday_id" => $holiday->id,
                            "date" => $current_date,
                            "hours" => $holiday_hours,
                        ]);
                        // $total_holiday_hours +=  $holiday_hours;
                    }
                }
                $holiday_start_date->addDay();
            }
        });



        $approved_attendances->each(function ($approved_attendance) use ( &$payroll_attendances_data  ) {

            // $attendance_in_date = Carbon::parse($approved_attendance->in_date)->format("Y-m-d");
            // $day_number = Carbon::parse($attendance_in_date)->dayOfWeek;

            // $work_shift_detail = $work_shift_details->get($day_number);
            // $is_weekend = 1;
            // // $capacity_hours = 0;
            // if ($work_shift_detail) {
            //     $is_weekend = $work_shift_detail->is_weekend;
            //     // $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->start_at);
            //     // $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->end_at);
            //     // $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
            // }

            // $leave_record = $payroll_leave_records_data->first(function ($leave_record) use ($attendance_in_date) {
            //     $leave_date = Carbon::parse($leave_record->date)->format("Y-m-d");
            //     return $attendance_in_date == $leave_date;
            // });

            // $holiday = $payroll_holidays_data->first(function ($holiday) use ($attendance_in_date) {
            //     $holiday_date = Carbon::parse($holiday->date);
            //     $attendance_in_date = Carbon::parse($attendance_in_date);
            //     return $attendance_in_date->eq($holiday_date);
            // });

            if ($approved_attendance->total_paid_hours > 0) {
                // $total_attendance_hours = $approved_attendance->total_paid_hours;
                // $holiday_id = NULL;
                // $leave_record_id = NULL;
                // $overtime_start_time = NULL;
                // $overtime_end_time = NULL;
                // $result_balance_hours = 0;
                // if ($approved_attendance->is_weekend  || $approved_attendance->holiday_id) {
                //     $overtime_start_time = $approved_attendance->in_time;
                //     $overtime_end_time = $approved_attendance->out_time;
                //     $result_balance_hours = $total_attendance_hours;

                // } else if ($approved_attendance->leave_record_id) {
                //     $attendance_in_time = Carbon::parse($approved_attendance->in_time);
                //     $attendance_out_time = Carbon::parse($approved_attendance->out_time);

                //     $leave_start_time = Carbon::parse($approved_attendance->leave_start_time);
                //     $leave_end_time = Carbon::parse($approved_attendance->leave_end_time);

                //     $balance_start_time = $attendance_in_time->max($leave_start_time);
                //     $balance_end_time = $attendance_out_time->min($leave_end_time);

                //     // Check if there is any overlap
                //     if ($balance_start_time < $balance_end_time) {
                //         $overtime_start_time = $approved_attendance->in_time;
                //         $overtime_end_time = $approved_attendance->out_time;

                //         $result_balance_hours = $balance_start_time->diffInHours($balance_end_time);

                //     }
                // } else if ($approved_attendance->work_hours_delta > 0) {
                //     $result_balance_hours = $approved_attendance->work_hours_delta;
                // }

                // $total_overtime_attendance_hours += $approved_attendance->result_balance_hours;
                // $total_regular_attendance_hours += $approved_attendance->regular_work_hours;
                $payroll_attendances_data->push([
                    "attendances" => $approved_attendance->id
            ]);

            }





        });









        $payroll_data =  [
            'user_id' => $employee->id,
            "payrun_id" => $payrun->id,

            // "total_holiday_hours" => $total_holiday_hours,
            // "total_paid_leave_hours" => $total_paid_leave_hours,
            // "total_regular_attendance_hours" => $total_regular_attendance_hours,
            // "total_overtime_attendance_hours" => $total_overtime_attendance_hours,

            // 'regular_hours' => $total_holiday_hours + $total_paid_leave_hours +  $total_regular_attendance_hours,
            // 'overtime_hours' => $total_overtime_attendance_hours,


            // "holiday_hours_salary" => $total_holiday_hours * $hourly_salary,
            // "leave_hours_salary" => $total_paid_leave_hours * $hourly_salary,
            // "regular_attendance_hours_salary" => $total_regular_attendance_hours * $hourly_salary,
            // "overtime_attendance_hours_salary" => $total_overtime_attendance_hours * $hourly_salary,

            // 'regular_hours_salary' => ($total_holiday_hours + $total_paid_leave_hours +  $total_regular_attendance_hours) * $hourly_salary,
            // 'overtime_hours_salary' => $total_overtime_attendance_hours * $overtime_salary,


            "hourly_salary" => $hourly_salary,
            "overtime_salary" => $overtime_salary,
            "holiday_hours" => $holiday_hours,


            'status' => "pending_approval",
            'is_active' => 1,
            'business_id' => $employee->business_id,
        ];




        if ($generate_payroll) {

            try {
                DB::transaction(function () use ($payroll_data, $payroll_holidays_data, $payroll_leave_records_data, $payroll_attendances_data, $attendance_arrears, $leave_arrears) {
                    $payroll = Payroll::create($payroll_data);
                    $payroll->payroll_holidays()->createMany($payroll_holidays_data->toArray());
                    $payroll->payroll_leave_records()->createMany($payroll_leave_records_data->toArray());
                    $payroll->payroll_attendances()->createMany($payroll_attendances_data->toArray());

                    $attendance_arrears->each(function ($attendance_arrear) {
                        AttendanceArrear::create([
                            "status" => "pending_approval",
                            "attendance_id" => $attendance_arrear->id
                        ]);
                    });

                    $leave_arrears->each(function ($leave_arrear) {
                        LeaveRecordArrear::create([
                            "status" => "pending_approval",
                            "leave_record_id" => $leave_arrear->id
                        ]);
                    });






                });
            } catch (Exception $e) {

                $this->storeError($e, 422, $e->getLine(), $e->getFile());
                return false;
                return [
                    "message" => "something went wrong creating payroll",
                ];
            }
        }


        return $payroll_data;
    }

    public function adjust_payroll_on_attendance_update($attendance) {

        DB::transaction(function() use($attendance) {
            $attendance_arrear =   AttendanceArrear:: where(["attendance_id" => $attendance->id]) ->first();
            $payroll = Payroll::whereHas("payroll_attendances", function($query) use($attendance) {

                $query->where("payroll_attendances.attendance_id",$attendance->id);

           })->first();
           if(!$payroll) {
            if(!$attendance_arrear) {
                $date = Carbon::parse($attendance["in_date"]);
                $current_date = Carbon::now();
                if ($date->diffInYears($current_date) >= 2) {
                    AttendanceArrear::create(["attendance_id" => $attendance->id,  "status" => "pending_approval"]);
                }
            }

              return true;
           }


           if($attendance->status != "approved" || $attendance->total_paid_hours < 0) {
            PayrollAttendance::where([
                "attendance_id" => $attendance->id,
                "payroll_id" => $payroll->id
            ])
            ->delete();
            if($attendance_arrear) {
               $attendance_arrear->update([
                "status" => "pending_approval",
               ]);
            }

        }
        if ($attendance->total_paid_hours > 0) {
            PayrollAttendance:: updateOrCreate(
                [
                    "attendance_id" => $attendance->id,
                    "payroll_id" => $payroll->id
                ],
                [
                    "attendances" => $attendance->id,
                    "payroll_id" => $payroll->id
            ]
            );
            if($attendance_arrear) {
                $attendance_arrear->update([
                 "status" => "approved",
                ]);
             }

        }

        });

        return true;


}


public function adjust_payroll_on_leave_update($leave_record) {

    DB::transaction(function() use($leave_record) {
        $leave_record_arrear =   LeaveRecordArrear:: where(["leave_record_id" => $leave_record->id])->first();


        $payroll = Payroll::whereHas("payroll_leave_records", function($query) use($leave_record) {
            $query->where("payroll_leave_records.leave_record_id",$leave_record->id);
       })->first();
       if(!$payroll) {
        if(!$leave_record_arrear) {
        $date = Carbon::parse($leave_record["date"]);
        $current_date = Carbon::now();
        if ($date->diffInYears($current_date) >= 2) {
            LeaveRecordArrear::create(["leave_record_id" => $leave_record->id,  "status" => "pending_approval"]);
        }
    }


          return true;
       }


       if($leave_record->leave->status != "approved" || $leave_record->leave->leave_type != "paid") {
        PayrollLeaveRecord::where([
            "leave_record_id" => $leave_record->id,
            "payroll_id" => $payroll->id
        ])
        ->delete();
        if($leave_record_arrear) {
           $leave_record_arrear->update([
            "status" => "pending_approval",
           ]);
        }
    }


    if ($leave_record->leave->leave_type == "paid") {
        PayrollLeaveRecord:: updateOrCreate(
            [
                "leave_record_id" => $leave_record->id,
                "payroll_id" => $payroll->id
            ],
            [
                "leave_record_id" => $leave_record->id,
                "payroll_id" => $payroll->id
        ]
        );
        if($leave_record_arrear) {
            $leave_record_arrear->update([
             "status" => "approved",
            ]);
         }

    }

    });

    return true;


}





public function recalculate_payroll($attendance) {

    DB::transaction(function() use($attendance) {
        $payroll = Payroll::whereHas("payroll_attendances", function($query) use($attendance) {

            $query->where("payroll_attendances.attendance_id",$attendance->id);

       })->first();
       if(!$payroll) {
          return true;
       }


       $payroll->total_paid_leave_hours = $payroll->payroll_leave_records->leave_record->whereHas(
        "leave.leave_type", function($query) {
                 $query->where("setting_leave_types.type","paid");
        }
    )
    ->sum("leave_hours");
    $payroll->leave_hours_salary = $payroll->total_paid_leave_hours * $payroll->hourly_salary;


    $payroll->total_regular_attendance_hours = $payroll->payroll_attendances->attendance
    ->sum("regular_work_hours");

    $payroll->total_regular_attendance_hours = $payroll->payroll_attendances->attendance
    ->sum("overtime_hours");

$payroll->regular_hours =  $payroll->total_holiday_hours +  $payroll->total_paid_leave_hours +   $payroll->total_regular_attendance_hours;
$payroll->overtime_hours = $payroll->total_overtime_attendance_hours;

 $payroll->regular_attendance_hours_salary = $payroll->total_regular_attendance_hours * $payroll->hourly_salary;
$payroll->overtime_attendance_hours_salary = $payroll->total_overtime_attendance_hours * $payroll->hourly_salary;

$payroll->regular_hours_salary = ($payroll->total_holiday_hours + $payroll->total_paid_leave_hours +  $payroll->total_regular_attendance_hours) * $payroll->hourly_salary;
$payroll->overtime_hours_salary = $payroll->total_overtime_attendance_hours * $payroll->overtime_salary;


        $payroll->save();



    });

    return true;

}


public function update_attendance_accordingly($attendance,$leave_record = NULL) {

    DB::transaction(function() use($attendance, $leave_record) {
        $result_balance_hours = 0;
        $overtime_start_time = NULL;
        $overtime_end_time = NULL;
        $leave_start_time = NULL;
        $leave_end_time = NULL;
        $leave_hours = 0;
        if ($attendance->is_weekend || $attendance->holiday_id) {
            $overtime_start_time = $attendance->in_time;
            $overtime_end_time = $attendance->out_time;
            $result_balance_hours = $attendance->total_paid_hours;

        } else if ($attendance->leave_record_id) {
            if(!$leave_record) {
                $leave_record = LeaveRecord::where([
                    "id"=> $attendance->leave_record_id
                ])
                ->first();
            }

              $attendance_in_time = $attendance->in_time;
              $attendance_out_time = $attendance->out_time;

              $leave_start_time = Carbon::parse($leave_record->start_time);
              $leave_end_time = Carbon::parse($leave_record->end_time);

              $balance_start_time = $attendance_in_time->max($leave_start_time);
              $balance_end_time = $attendance_out_time->min($leave_end_time);

              // Check if there is any overlap
              if ($balance_start_time < $balance_end_time) {
                  $overtime_start_time = $attendance->in_time;
                  $overtime_end_time = $attendance->out_time;
                  $result_balance_hours = $balance_start_time->diffInHours($balance_end_time);


                  $uncommon_attendance_start = $attendance_in_time->min($balance_start_time);
                  $uncommon_attendance_end = $attendance_out_time->max($balance_end_time);
                  $uncommon_leave_start = $leave_start_time->min($balance_start_time);
                  $uncommon_leave_end = $leave_end_time->max($balance_end_time);

              } else {
                  $uncommon_attendance_start = $attendance_in_time;
                  $uncommon_attendance_end = $attendance_out_time;

                  $uncommon_leave_start = $leave_start_time;
                  $uncommon_leave_end = $leave_end_time;
              }

              $uncommon_attendance_hours = $uncommon_attendance_start->diffInHours($uncommon_attendance_end);
              $uncommon_leave_hours = $uncommon_leave_start->diffInHours($uncommon_leave_end);

              $leave_hours = $$attendance->capacity_hours - ($uncommon_attendance_hours + $uncommon_leave_hours + $result_balance_hours);


        } else if ($attendance->work_hours_delta > 0) {
            $result_balance_hours = $attendance->work_hours_delta;
        }  else if ($attendance->work_hours_delta < 0) {
            $leave_hours = abs($attendance->work_hours_delta);
        }

     $regular_work_hours =  $attendance->total_paid_hours - $result_balance_hours;



       $attendance->update([
        "regular_work_hours" => $regular_work_hours,
        "overtime_start_time" => $overtime_start_time,
        "overtime_end_time" => $overtime_end_time,
        "overtime_hours" => $result_balance_hours,
        "leave_start_time" => $leave_start_time,
        "leave_end_time" => $leave_end_time,
        "leave_record_id" => $leave_record?$leave_record->id:NULL,
        "leave_hours" => $leave_hours,
        // "total_paid_hours" => $regular_work_hours + $result_balance_hours,

       ]);



    });

    return true;

}








}
