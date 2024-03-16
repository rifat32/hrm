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
use App\Models\SalaryHistory;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Exception;


trait PayrunUtil
{
    use BasicUtil;
    public function get_salary_info($user_id, $date)
    {

        $salary_history = SalaryHistory::where([
            "user_id" => $user_id
        ])
            ->where("from_date", "<=", $date)
            ->where(function ($query) use ($date) {
                $query->where("to_date", ">", $date)
                    ->orWhereNull("to_date");
            })

            ->orderByDesc("to_date")
            ->first();

        if (!$salary_history) {
            throw new Exception("No Salary History found", 400);
        }

        $salary_per_annum = $salary_history->salary_per_annum; // in euros
        $weekly_contractual_hours = $salary_history->weekly_contractual_hours;
        $weeks_per_year = 52;
        if (!$weekly_contractual_hours) {
            $hourly_salary = 0;
        } else {
            $hourly_salary = $salary_per_annum / ($weeks_per_year * $weekly_contractual_hours);
        }

        $overtime_salary_per_hour = $salary_history->overtime_rate ? $salary_history->overtime_rate : $hourly_salary;

        if (!$weekly_contractual_hours || !$salary_history->minimum_working_days_per_week) {
            $holiday_considered_hours = 0;
        } else {
            $holiday_considered_hours = $weekly_contractual_hours / $salary_history->minimum_working_days_per_week;
        }

        return [
            "hourly_salary" => $hourly_salary,
            "overtime_salary_per_hour" => $overtime_salary_per_hour,
            "holiday_considered_hours" => $holiday_considered_hours
        ];
    }

    // this function do all the task and returns transaction id or -1
    public function process_payrun($payrun, $employees, $start_date, $end_date = NULL, $is_manual = false, $generate_payroll = false)
    {

        if (!$payrun->business_id) {
            return false;
        }
        // $end_date = $payrun->end_date;

        // Set end_date based on period_type
        if (!$start_date || !$end_date) {
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
                        $start_date = Carbon::now()->startOfMonth()->subMonth(1);
                    }
                    if (!$end_date) {
                        $end_date = Carbon::now()->startOfMonth()->subDay(1);
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
        if (!$end_date->isToday() && $is_manual == false) {
            return false; // Skip to the next iteration
        }

        $employees->each(function ($employee) use ($payrun, $generate_payroll, $start_date, $end_date) {
            $employee->payroll = $this->generate_payroll($payrun, $employee, $start_date, $end_date, $generate_payroll);
            return $employee;
        });

        return $employees;
    }


    public function generate_payroll($payrun, $employee, $start_date, $end_date,  $generate_payroll)
    {

        $all_parent_department_ids = $this->all_parent_departments_of_user($employee->id);
        $attendance_arrears = Attendance::whereDoesntHave("payroll_attendance")
            ->where('attendances.user_id', $employee->id)

            ->where(function ($query) use ($start_date, $end_date) {
                $query->where(function ($query) use ($start_date, $end_date) {
                    $query->whereNotIn("attendances.status", ["approved"])
                        ->where('attendances.in_date', '<=', $end_date)
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

            ->where(function ($query) use ($start_date, $end_date) {
                $query->where(function ($query) use ($start_date, $end_date) {
                    $query
                        ->whereHas('leave',    function ($query) {
                            $query->whereNotIn("leaves.status", ["approved"]);
                        })
                        ->where('leave_records.date', '<=', $end_date)
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
            ->where(function ($query) use ($start_date, $end_date) {
                $query
                    ->where(function ($query) use ($start_date, $end_date) {
                        $query
                            ->where('attendances.in_date', '<=', $end_date)
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
            ->where(function ($query) use ($start_date, $end_date) {
                $query->where(function ($query) use ($start_date, $end_date) {
                    $query

                        ->where('leave_records.date', '<=', $end_date)
                        ->where('leave_records.date', '>=', $start_date);
                })
                    ->orWhere(function ($query) {
                        $query->whereHas("arrear", function ($query) {
                            $query->where("leave_record_arrears.status", "approved");
                        });
                    });
            })
            ->get();


        $holidays = Holiday::whereDoesntHave("payroll_holiday.payroll", function ($query) use ($employee) {
                $query->where([
                    "payrolls.user_id" => $employee->id
                ]);
            })
            ->where([
                "business_id" => auth()->user()->business_id
            ])
            ->where('holidays.start_date', '<=', $end_date)
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

        $payroll_attendances_data = collect();
        $payroll_leave_records_data = collect();
        $payroll_holidays_data = collect();

        $approved_leave_records->each(function ($approved_leave_record) use (&$payroll_leave_records_data) {
            if ($approved_leave_record->leave->leave_type->type == "paid") {
                // $total_paid_leave_hours += $approved_leave_record->leave_hours;
                $payroll_leave_records_data->push([
                    "leave_record_id" => $approved_leave_record->id,

                ]);
            }
        });

        $date_range = collect();
        $holidays->each(function ($holiday) use (&$date_range, &$payroll_holidays_data, $end_date, $employee) {

            $holiday_start_date = Carbon::parse($holiday->start_date);
            $holiday_end_date = Carbon::parse($holiday->end_date);

            while ($holiday_start_date->lte($holiday_end_date)) {
                $current_date = $holiday_start_date->format("Y-m-d");
                // Check if the date is not already in the collection before adding
                if (!$date_range->contains($current_date)) {
                    $date_range->push($current_date);
                    if (Carbon::parse($current_date)->between(Carbon::parse($end_date), $holiday_start_date)) {
                        $user_salary_info = $this->get_salary_info($employee->id, $current_date);
                        $payroll_holidays_data->push([
                            "holiday_id" => $holiday->id,
                            "date" => $current_date,
                            "hours" => $user_salary_info["holiday_considered_hours"],
                            "hourly_salary" => $user_salary_info["hourly_salary"],
                        ]);
                    }
                }
                $holiday_start_date->addDay();
            }
        });

        $approved_attendances->each(function ($approved_attendance) use (&$payroll_attendances_data) {
            if ($approved_attendance->total_paid_hours > 0) {

                $payroll_attendances_data->push([
                    "attendance_id" => $approved_attendance->id
                ]);
            }
        });

        $payroll_data =  [
            'user_id' => $employee->id,
            "payrun_id" => $payrun->id,
            'status' => "pending_approval",
            'is_active' => 1,
            'business_id' => $employee->business_id,

            "start_date" => $start_date,
            "end_date" => $end_date,
        ];
        $payroll_data['payroll_name'] = $this->generate_payroll_name($payrun);

        $temp_payroll = null;
        if ($generate_payroll) {
            try {


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

                    $recalculate_payroll_values = $this->recalculate_payroll_values($payroll);
                    if (!$recalculate_payroll_values) {
                        throw new Exception("some thing went wrong");
                    }
                    $temp_payroll = clone $recalculate_payroll_values;

            } catch (Exception $e) {
                $this->storeError($e, 422, $e->getLine(), $e->getFile());
                return false;
                return [
                    "message" => "something went wrong creating payroll",
                ];
            }
        } else {
            try {

                    $payroll = Payroll::create($payroll_data);
                    $payroll->payroll_holidays()->createMany($payroll_holidays_data->toArray());
                    $payroll->payroll_leave_records()->createMany($payroll_leave_records_data->toArray());

                    $payroll->payroll_attendances()->createMany($payroll_attendances_data->toArray());



                    $recalculate_payroll_values = $this->recalculate_payroll_values($payroll);
                    if (!$recalculate_payroll_values) {
                        throw new Exception("some thing went wrong");
                    }

                    $temp_payroll = clone $recalculate_payroll_values;
                    $payroll->delete();

            } catch (Exception $e) {

                $this->storeError($e, 422, $e->getLine(), $e->getFile());

                return [
                    "message" => $e->getMessage(),
                ];
            }
        }
        return $temp_payroll;
    }
    public  function generate_payroll_name($payrun)
    {
        // Define variables for the payroll name
        $period_type = $payrun->period_type;
        $end_date = $payrun->end_date;

        // Generate the payroll name based on the period type
        if ($period_type == 'weekly') {
            $payroll_name = date('d-m-Y', strtotime($end_date)) . '_weekly_payroll';
        } elseif ($period_type == 'monthly') {
            $payroll_name = date('m-Y', strtotime($end_date)) . '_monthly_payroll';
        } elseif ($period_type == 'customized') {
            // Assuming you have a start_date field in $payrun object
            $start_date = $payrun->start_date;
            $payroll_name = date('d-m-Y', strtotime($start_date)) . '_to_' . date('d-m-Y', strtotime($end_date)) . '_customized_payroll';
        } else {
            // Default case, if period_type is not recognized
            $payroll_name = 'unknown_payroll';
        }

        return $payroll_name;
    }
    private function create_attendance_arrear($attendance, $add_to_next_payroll)
    {
        $last_payroll_exists = Payroll::where([
            "user_id" => $attendance->user_id,
        ])
            ->where("start_date", ">", $attendance["in_date"])
            ->exists();

        if ($last_payroll_exists) {
            AttendanceArrear::create(["attendance_id" => $attendance->id,  "status" => "pending_approval"]);
        }
    }
    public function adjust_payroll_on_attendance_update($attendance, $add_to_next_payroll = 0)
    {

        $attendance_arrear =   AttendanceArrear::where(["attendance_id" => $attendance->id])->first();
        $payroll = Payroll::whereHas("payroll_attendances", function ($query) use ($attendance) {
            $query->where("payroll_attendances.attendance_id", $attendance->id);
        })->first();

        if (!$payroll) {
            if (!$attendance_arrear) {
                $this->create_attendance_arrear($attendance, $add_to_next_payroll);
            }
            return true;
        }

        if ($attendance->status != "approved" || $attendance->total_paid_hours < 0) {
            PayrollAttendance::where([
                "attendance_id" => $attendance->id,
                "payroll_id" => $payroll->id
            ])
                ->delete();
            if ($attendance_arrear) {
                $attendance_arrear->update([
                    "status" => "pending_approval",
                ]);
            } else {
                $this->create_attendance_arrear($attendance, 0);
            }
        } else if ($attendance->total_paid_hours > 0) {
            if ($attendance_arrear) {
                $attendance_arrear->update([
                    "status" => 1 ? "approved" : "pending_approval",

                ]);
            }
        }

        $this->recalculate_payroll_values($payroll);

        return true;
    }

    private function create_leave_arrear($leave_record, $add_to_next_payroll)
    {
        $last_payroll_exists = Payroll::where([
            "user_id" => $leave_record->leave->user_id,
        ])
            ->where("start_date", ">", $leave_record["date"])
            ->exists();

        if ($last_payroll_exists) {
            LeaveRecordArrear::create([
                "leave_record_id" => $leave_record->id,
                "status" => $add_to_next_payroll ? "approved" : "pending_approval",
            ]);
        }
    }

    public function adjust_payroll_on_leave_update($leave_record, $add_to_next_payroll = 0)
    {

        $leave_record_arrear =   LeaveRecordArrear::where(["leave_record_id" => $leave_record->id])->first();
        $payroll = Payroll::whereHas("payroll_leave_records", function ($query) use ($leave_record) {
            $query->where("payroll_leave_records.leave_record_id", $leave_record->id);
        })->first();

        if (!$payroll) {
            if (!$leave_record_arrear) {
                $this->create_leave_arrear($leave_record, $add_to_next_payroll);
            }
            return true;
        }

        if ($leave_record->leave->status != "approved" || $leave_record->leave->leave_type != "paid") {
            PayrollLeaveRecord::where([
                "leave_record_id" => $leave_record->id,
                "payroll_id" => $payroll->id
            ])
                ->delete();

            if ($leave_record_arrear) {
                $leave_record_arrear->update([
                    "status" => "pending_approval",
                ]);
            } else {
                $this->create_leave_arrear($leave_record, 0);
            }
        } else if ($leave_record->leave_hours > 0) {
            if ($leave_record_arrear) {
                $leave_record_arrear->update([
                    "status" => 1 ? "approved" : "pending_approval",
                ]);
            }
        }

        $this->recalculate_payroll_values($payroll);

        return true;
    }





    public function recalculate_payroll($attendance)
    {
        $payroll = Payroll::whereHas("payroll_attendances", function ($query) use ($attendance) {
            $query->where("payroll_attendances.attendance_id", $attendance->id);
        })->first();
        if (!$payroll) {
            return true;
        }
        if (!$this->recalculate_payroll_values($payroll)) {
            return false;
        }
        return true;
    }

    public function recalculate_payrolls($payrolls)
    {
        foreach ($payrolls as $payroll) {
            $this->recalculate_payroll_values($payroll);
        }
    }
    public function recalculate_payroll_values($payroll)
    {

        if ($payroll->payroll_holidays->isNotEmpty()) {
            $total_holiday_hours = 0;
            $total_holiday_hours_salary = 0;

            foreach ($payroll->payroll_holidays as $payroll_holiday) {
                $total_holiday_hours += $payroll_holiday->hours;
                $total_holiday_hours_salary += ($payroll_holiday->hours * $payroll_holiday->hourly_salary);
            }
            $payroll->total_holiday_hours = $total_holiday_hours;
            $payroll->total_holiday_hours_salary = $total_holiday_hours_salary;
        } else {
            // Set total_paid_leave_hours to 0 if payroll_leave_records is empty
            $payroll->total_holiday_hours = 0;
            $payroll->total_holiday_hours_salary = 0;
        }


        if ($payroll->payroll_leave_records->isNotEmpty()) {
            $total_paid_leave_hours = 0;
            $leave_hours_salary = 0;
            foreach ($payroll->payroll_leave_records as $payroll_leave_record) {
                if ($payroll_leave_record->leave_record && $payroll_leave_record->leave_record->leave) {
                    // Loop through each leave record
                    foreach ($payroll_leave_record->leave_record->whereHas("leave.leave_type", function ($query) {
                        $query->where("setting_leave_types.type", "paid");
                    }) as $leave_record) {
                        // Add leave hours to $total_paid_leave_hours
                        $total_paid_leave_hours += $leave_record->leave_hours;
                        $leave_hours_salary += $leave_record->leave->hourly_rate;
                    }
                }
            }
            $payroll->total_paid_leave_hours = $total_paid_leave_hours;
            $payroll->leave_hours_salary = $leave_hours_salary;
        } else {
            // Set total_paid_leave_hours to 0 if payroll_leave_records is empty
            $payroll->total_paid_leave_hours = 0;
            $payroll->leave_hours_salary = 0;
        }

        $total_attendance_salary = 0;
        $regular_attendance_hours_salary = 0;
        $overtime_attendance_hours_salary = 0;
        if ($payroll->payroll_attendances->isNotEmpty()) {
            $total_regular_attendance_hours = 0;
            $overtime_hours = 0;

            foreach ($payroll->payroll_attendances as $payroll_attendance) {
                if ($payroll_attendance->attendance) {
                    $total_regular_attendance_hours += $payroll_attendance->attendance->regular_work_hours;
                    $overtime_hours += $payroll_attendance->attendance->overtime_hours;

                    $total_attendance_salary += ($payroll_attendance->attendance->regular_hours_salary + $payroll_attendance->attendance->overtime_hours_salary);

                    $regular_attendance_hours_salary += $payroll_attendance->attendance->regular_hours_salary;
                    $overtime_attendance_hours_salary += $payroll_attendance->attendance->overtime_hours_salary;
                }
            }

            $payroll->total_regular_attendance_hours = $total_regular_attendance_hours;
            $payroll->overtime_hours = $overtime_hours;
        } else {
            // Set both total_regular_attendance_hours and overtime_hours to 0 if payroll_attendances is empty
            $payroll->total_regular_attendance_hours = 0;
            $payroll->overtime_hours = 0;
        }

        $payroll->regular_hours =  $payroll->total_holiday_hours +  $payroll->total_paid_leave_hours +   $payroll->total_regular_attendance_hours;
        $payroll->regular_hours_salary = $payroll->total_holiday_hours_salary  + $payroll->leave_hours_salary +  $total_attendance_salary;
        $payroll->overtime_hours_salary = $overtime_attendance_hours_salary;
        $payroll->regular_attendance_hours_salary = $regular_attendance_hours_salary;
        $payroll->overtime_attendance_hours_salary = $overtime_attendance_hours_salary;

        $payroll->save();
        return $payroll;
    }
}
