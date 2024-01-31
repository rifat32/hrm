<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceArrear;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\LeaveRecordArrear;
use App\Models\Payrun;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {




        DB::transaction(function () {
            $payruns = Payrun::where('is_active', true)->get();

            foreach ($payruns as $payrun) {

                $start_date = $payrun->start_date;
                $end_date = $payrun->end_date;

                if (!$payrun->business_id) {
                    continue;
                }
                // Set end_date based on period_type
                switch ($payrun->period_type) {
                    case 'weekly':
                        $start_date = Carbon::now()->startOfWeek()->subWeek(1);
                        $end_date = Carbon::now()->startOfWeek();
                        break;
                    case 'monthly':
                        $start_date = Carbon::now()->startOfMonth()->addMonth(1);
                        $end_date = Carbon::now()->startOfMonth();
                        break;
                }
                if (!$start_date || !$end_date) {
                    continue; // Skip to the next iteration
                }

                // Convert end_date to Carbon instance
                $end_date = Carbon::parse($end_date);

                // Check if end_date is today
                if (!$end_date->isToday()) {
                    continue; // Skip to the next iteration
                }

                $employees = User::where([
                    "business_id" => $payrun->business_id,
                    "is_active" => 1
                ])
                    ->get();

                foreach ($employees as $employee) {
                    $work_shift =   WorkShift::whereHas('users', function ($query) use ($employee) {
                        $query->where('users.id', $employee->id);
                    })->first();

                    if (!$work_shift) {
                        return false;
                    }

                    if (!$work_shift->is_active) {
                        return false;
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
                    $weeksPerYear = 52;
                    $hourly_salary = $salary_per_annum / ($weeksPerYear * $weekly_contractual_hours);
                    $holiday_hours = $employee->weekly_contractual_hours / $employee->minimum_working_days_per_week;


                    $attendance_arrears = Attendance::
                    whereDoesntHave("payroll")
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

                    foreach ($attendance_arrears as $attendance_arrear) {
                        AttendanceArrear::create([
                            "status" => "pending_approval",
                            "attendance_id" => $attendance_arrear->id
                        ]);
                    }


                    $leave_arrears = LeaveRecord::
                    whereDoesntHave("payroll")
                    ->whereHas('leave',    function ($query) use ($employee)  {
                        $query->where("leaves.user_id",  $employee->id);
                    })

                        ->where(function ($query) use ($start_date) {
                            $query->where(function ($query) use ($start_date) {
                                $query
                                ->whereHas('leave',    function ($query)  {
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



                    foreach ($leave_arrears as $leave_arrear) {
                        LeaveRecordArrear::create([
                            "status" => "pending_approval",
                            "leave_record_id" => $leave_arrear->id
                        ]);
                    }



                    $approved_attendances = Attendance::
                    whereDoesntHave("payroll")
                        ->where('attendances.user_id', $employee->id)
                        ->where(function ($query) use ($start_date) {
                            $query->where(function ($query) use ($start_date) {
                                $query
                                ->where("attendances.status", "approved")
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



                    $approved_leave_records = LeaveRecord::
                        whereDoesntHave("payroll")
                        ->whereHas('leave',    function ($query) use ($employee)  {
                            $query->where("leaves.user_id",  $employee->id);
                        })
                            ->where(function ($query) use ($start_date) {
                                $query->where(function ($query) use ($start_date) {
                                    $query
                                    ->whereHas('leave',    function ($query)   {
                                        $query
                                        ->where("leaves.status", "approved");
                                    })
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
                    ->where('holidays.end_date', '<=', today()->endOfDay())
                    // ->where('holidays.end_date', '>=', $start_date)
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


                    $total_paid_hours = 0;
                    $total_balance_hours = 0;

                    foreach($approved_attendances as $approved_attendance) {
                        $in_date = Carbon::parse($approved_attendance->in_date)->format("Y-m-d");
                        $day_number = Carbon::parse($in_date)->dayOfWeek;
                        $work_shift_detail = $work_shift_details->get($day_number);

                        $is_weekend = 1;
                        $capacity_hours = 0;
                        if ($work_shift_detail) {
                            $is_weekend = $work_shift_detail->is_weekend;
                            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->start_at);
                            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->end_at);
                            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
                        }


                        $leave_record = $approved_leave_records->first(function ($leave_record) use ($in_date,)  {
                            $leave_date = Carbon::parse($leave_record->date)->format("Y-m-d");
                           return $in_date != $leave_date;
                        });
                        $holiday = $holidays->first(function ($holiday) use ($in_date,)  {
                            $start_date = Carbon::parse($holiday->start_date);
                            $end_date = Carbon::parse($holiday->end_date);
                            $in_date = Carbon::parse($in_date);

                            // Check if $in_date is within the range of start_date and end_date
                            return $in_date->between($start_date, $end_date, true);
                        });



                        if($approved_attendance->total_paid_hours > 0) {

                            $total_attendance_hours = $approved_attendance->total_paid_hours;

                            if ($leave_record || $holiday || $is_weekend) {
                                $result_balance_hours = $total_attendance_hours;
                            } elseif ($approved_attendance->work_hours_delta > 0) {
                                $result_balance_hours = $approved_attendance->work_hours_delta;
                            }
                            
                            $total_paid_hours += $total_attendance_hours;
                            $total_balance_hours += $result_balance_hours;
                        }
                    }












                }







                // Save the updated payrun
                $payrun->save();
            }
        });
    }
}
