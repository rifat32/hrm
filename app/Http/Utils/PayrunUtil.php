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

    public function getLeaveRecordData($approved_leave_records)
    {
        $payroll_leave_records_data = collect();
        $approved_leave_records->each(function ($approved_leave_record) use (&$payroll_leave_records_data) {
            if ($approved_leave_record->leave->leave_type->type == "paid") {
                // $total_paid_leave_hours += $approved_leave_record->leave_hours;
                $payroll_leave_records_data->push([
                    "leave_record_id" => $approved_leave_record->id,

                ]);
            }
        });
        return $payroll_leave_records_data;
    }





    public function getHolidaysDataV2($holidays,$start_date, $end_date, $employee)
    {
        $salaryHistories = $this->get_salary_infos($employee->id, $start_date, $end_date);
        $payroll_holidays_data = collect();
        $date_range = collect();
        $holidays->each(function ($holiday) use (&$date_range, &$payroll_holidays_data, $end_date, $employee, $salaryHistories) {
            $holiday_start_date = Carbon::parse($holiday->start_date);
            $holiday_end_date = Carbon::parse($holiday->end_date);
            while ($holiday_start_date->lte($holiday_end_date)) {
                $current_date = $holiday_start_date->format("Y-m-d");
                // Check if the date is not already in the collection before adding
                if (!$date_range->contains($current_date)) {
                    $date_range->push($current_date);
                    if (Carbon::parse($current_date)->between(Carbon::parse($end_date), $holiday_start_date)) {
                        $user_salary_info = $salaryHistories->first(function ($history) use ($current_date) {
                            $current_date = Carbon::parse($current_date);
                            $fromDate = Carbon::parse($history["from_date"]);
                            $toDate = $history["to_date"] ? Carbon::parse($history["to_date"]) : null;

                            return $current_date->greaterThanOrEqualTo($fromDate)
                                && ($toDate === null || $current_date->lessThan($toDate));
                        });

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

        return $payroll_holidays_data;
    }

    public function getHolidaysData($holidays, $end_date, $employee)
    {
        $payroll_holidays_data = collect();
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

        return $payroll_holidays_data;
    }

    public function getAttendanceData($approved_attendances)
    {
        $payroll_attendances_data = collect();
        $approved_attendances->each(function ($approved_attendance) use (&$payroll_attendances_data) {
            if ($approved_attendance->total_paid_hours > 0) {

                $payroll_attendances_data->push([
                    "attendance_id" => $approved_attendance->id
                ]);
            }
        });

        return $payroll_attendances_data;
    }








    public function getHolidaysV2($all_parent_department_ids, $employee, $start_date, $end_date)
    {
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
            ->select('holidays.id', 'holidays.start_date', 'holidays.end_date')

            ->get();
        return $holidays;
    }
    public function getHolidays($all_parent_department_ids, $employee, $start_date, $end_date)
    {
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
        return $holidays;
    }


    public function getAttendanceArrears($start_date, $end_date, $employee)
    {
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
        return $attendance_arrears;
    }

    public function getAttendanceArrearsV2($start_date, $end_date, $employee)
    {
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
            ->get(["attendances.id"]);
        return $attendance_arrears;
    }
    public function getLeaveRecordArrears($start_date, $end_date, $employee)
    {

        $leave_arrears = LeaveRecord::whereDoesntHave("payroll_leave_record")
            ->whereHas('leave', function ($query) use ($employee) {
                $query->where("leaves.user_id", $employee->id);
            })

            ->where(function ($query) use ($start_date, $end_date) {
                $query->where(function ($query) use ($start_date, $end_date) {
                    $query
                        ->whereHas('leave', function ($query) {
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
        return $leave_arrears;
    }
    public function getLeaveRecordArrearsV2($start_date, $end_date, $employee)
    {

        $leave_arrears = LeaveRecord::whereDoesntHave("payroll_leave_record")
            ->whereHas('leave', function ($query) use ($employee) {
                $query->where("leaves.user_id", $employee->id);
            })

            ->where(function ($query) use ($start_date, $end_date) {
                $query->where(function ($query) use ($start_date, $end_date) {
                    $query
                        ->whereHas('leave', function ($query) {
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
            ->get(["leave_records.id"]);
        return $leave_arrears;
    }

    public function getApprovedAttendances($start_date, $end_date, $employee)
    {
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
        return $approved_attendances;
    }
    public function getApprovedAttendancesV2($start_date, $end_date, $employee)
    {
        $approved_attendances = Attendance::whereDoesntHave("payroll_attendance")
        ->where("attendances.total_paid_hours", ">", 0)
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
            ->select(
                'attendances.id', 'attendances.regular_work_hours', 'attendances.overtime_hours', 'attendances.regular_hours_salary', 'attendances.overtime_hours_salary'
            )
            ->get();
        return $approved_attendances;
    }

    public function getApprovedLeaveRecords($start_date, $end_date, $employee)
    {
        $approved_leave_records = LeaveRecord::whereDoesntHave("payroll_leave_record")
            ->whereHas('leave', function ($query) use ($employee) {
                $query->where("leaves.user_id", $employee->id)
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
        return $approved_leave_records;
    }
    public function getApprovedLeaveRecordsV2($start_date, $end_date, $employee)
    {
        $approved_leave_records = LeaveRecord::
        with([
            "leave" => function ($query) use ($employee) {
                $query->select('leaves.id', 'leaves.hourly_rate');
            }
        ])
        ->whereDoesntHave("payroll_leave_record")
            ->whereHas('leave.leave_type', function ($query) use ($employee) {
                $query->where("leaves.user_id", $employee->id)
                    ->where("leaves.status", "approved")
                    ->where("setting_leave_types.name","paid");
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
            ->select('leave_records.id', 'leave_records.leave_hours')
            ->get();


        return $approved_leave_records;
    }


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
            throw new Exception("No Salary History found on date " . $date, 400);
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

    public function get_salary_infos($user_id, $start_date, $end_date)
    {
        $salary_histories = SalaryHistory::where([
            "user_id" => $user_id
        ])
            ->where("from_date", "<=", $end_date)
            ->where(function ($query) use ($start_date) {
                $query->where("to_date", ">", $start_date)
                    ->orWhereNull("to_date");
            })

            ->orderByDesc("to_date")
            ->get()->map(function ($salary_history) {
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
                    "id" => $salary_history->id,
                    "from_date" => $salary_history->from_date,
                    "to_date" => $salary_history->to_date,
                    "hourly_salary" => $hourly_salary,
                    "overtime_salary_per_hour" => $overtime_salary_per_hour,
                    "holiday_considered_hours" => $holiday_considered_hours
                ];
            });

        return $salary_histories;


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
    public function process_payrun_v2($payrun, $employees, $start_date, $end_date)
    {
        $employees->each(function ($employee) use ($payrun, $start_date, $end_date) {
            $employee->payroll = $this->generate_payroll_v2($payrun, $employee, $start_date, $end_date);
            return $employee;
        });

        return $employees;
    }
    public function estimate_payrun_data_v2($employees, $start_date, $end_date)
    {

        // Convert end_date to Carbon instance
        $end_date = Carbon::parse($end_date);

        collect($employees->items())->each(function ($employee) use ($start_date, $end_date) {
            $employee->payroll = $this->estimate_payroll_v2($employee, $start_date, $end_date);
            return $employee;
        });

        return $employees;
    }

    public function estimate_payroll_v2($employee, $start_date, $end_date)
    {

        $all_parent_department_ids = $this->all_parent_departments_of_user($employee->id);


        $approved_attendances = $this->getApprovedAttendancesV2($start_date, $end_date, $employee);


        $approved_leave_records = $this->getApprovedLeaveRecordsV2($start_date, $end_date, $employee);


        $holidays = $this->getHolidaysV2($all_parent_department_ids, $employee, $start_date, $end_date);

        $approved_holidays = collect($this->getHolidaysDataV2($holidays,$start_date, $end_date, $employee));



        $payroll_data = [
            'user_id' => $employee->id,
            'status' => "pending_approval",
            'is_active' => 1,
            'business_id' => $employee->business_id,

            "start_date" => $start_date,
            "end_date" => $end_date,


        ];


        $recalculate_payroll_values = $this->recalculate_payroll_values_v2($payroll_data,$approved_attendances,$approved_leave_records,$approved_holidays);
        if (!$recalculate_payroll_values) {
            throw new Exception("some thing went wrong");
        }




        return $recalculate_payroll_values;
    }

    // this function do all the task and returns transaction id or -1
    public function estimate_payrun_data($employees, $start_date, $end_date)
    {
        // Convert end_date to Carbon instance
        $end_date = Carbon::parse($end_date);

        $employees->each(function ($employee) use ($start_date, $end_date) {

            $employee->payroll = $this->estimate_payroll($employee, $start_date, $end_date);
            return $employee;
        });

        return $employees;
    }
    public function estimate_payroll($employee, $start_date, $end_date)
    {

        $all_parent_department_ids = $this->all_parent_departments_of_user($employee->id);



        $approved_attendances = $this->getApprovedAttendances($start_date, $end_date, $employee);


        $approved_leave_records = $this->getApprovedLeaveRecords($start_date, $end_date, $employee);


        $holidays = $this->getHolidays($all_parent_department_ids, $employee, $start_date, $end_date);

        $payroll_attendances_data = collect($this->getAttendanceData($approved_attendances));
        $payroll_leave_records_data = collect($this->getLeaveRecordData($approved_leave_records));
        $payroll_holidays_data = collect($this->getHolidaysData($holidays, $end_date, $employee));



        $payroll_data = [
            'user_id' => $employee->id,
            'status' => "pending_approval",
            'is_active' => 1,
            'business_id' => $employee->business_id,

            "start_date" => $start_date,
            "end_date" => $end_date,
        ];



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


        return $temp_payroll;
    }



    public function generate_payroll_v2($payrun, $employee, $start_date, $end_date)
    {

        $all_parent_department_ids = $this->all_parent_departments_of_user($employee->id);

        $attendance_arrears_ids = collect($this->getAttendanceArrearsV2($start_date, $end_date, $employee));

        $leave_arrears_ids = collect($this->getLeaveRecordArrearsV2($start_date, $end_date, $employee));


        $approved_attendances = collect($this->getApprovedAttendancesV2($start_date, $end_date, $employee));


        $approved_leave_records = collect($this->getApprovedLeaveRecordsV2($start_date, $end_date, $employee));



        $holidays = $this->getHolidays($all_parent_department_ids, $employee, $start_date, $end_date);

        $payroll_attendances_data = $approved_attendances->map(function($attendance) {
            return      ["attendance_id" => $attendance->id];
        });
        $payroll_leave_records_data =  $approved_leave_records->map(function($leave_record) {
            return      ["leave_record_id" => $leave_record->id];
                   });

        $payroll_holidays_data = collect($this->getHolidaysData($holidays, $end_date, $employee));



        $payroll_data = [
            'user_id' => $employee->id,
            "payrun_id" => $payrun->id,
            'status' => "pending_approval",
            'is_active' => 1,
            'business_id' => $employee->business_id,
            "start_date" => $start_date,
            "end_date" => $end_date,
        ];
        $payroll_data['payroll_name'] = $this->generate_payroll_name($payrun);

        $payroll_data = $this->recalculate_payroll_values_v2($payroll_data,$approved_attendances,$approved_leave_records,$payroll_holidays_data);




            $payroll = Payroll::create($payroll_data);


            $payroll->payroll_holidays()->createMany($payroll_holidays_data->toArray());
            $payroll->payroll_leave_records()->createMany($payroll_leave_records_data->toArray());
            $payroll->payroll_attendances()->createMany($payroll_attendances_data->toArray());

            $attendance_arrears_data = $attendance_arrears_ids->map(function ($attendance_arrear) {
                return [
                    "status" => "pending_approval",
                    "attendance_id" => $attendance_arrear->id,
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            })->toArray();

            AttendanceArrear::insert($attendance_arrears_data);


            $leave_arrears_data = $leave_arrears_ids->map(function ($leave_arrear) {
                return [
                    "status" => "pending_approval",
                    "leave_record_id" => $leave_arrear->id,
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            })->toArray();

            LeaveRecordArrear::insert($leave_arrears_data);


            AttendanceArrear::where([
                "status" => "pending_approval",

            ])
                ->whereIn("attendance_id", $payroll_attendances_data->pluck("id"))
                ->update([
                    "status" => "completed",
                ]);
            LeaveRecordArrear::whereIn("leave_record_id", $payroll_leave_records_data->pluck("id"))
                ->update([
                    "status" => "completed",
                ]);







        return $payroll;
    }
    public function generate_payroll($payrun, $employee, $start_date, $end_date, $generate_payroll)
    {

        $all_parent_department_ids = $this->all_parent_departments_of_user($employee->id);

        $attendance_arrears = $this->getAttendanceArrears($start_date, $end_date, $employee);

        $leave_arrears = $this->getLeaveRecordArrears($start_date, $end_date, $employee);


        $approved_attendances = $this->getApprovedAttendances($start_date, $end_date, $employee);


        $approved_leave_records = $this->getApprovedLeaveRecords($start_date, $end_date, $employee);



        $holidays = $this->getHolidays($all_parent_department_ids, $employee, $start_date, $end_date);

        $payroll_attendances_data = collect($this->getAttendanceData($approved_attendances));
        $payroll_leave_records_data = collect($this->getLeaveRecordData($approved_leave_records));
        $payroll_holidays_data = collect($this->getHolidaysData($holidays, $end_date, $employee));






        $payroll_data = [
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


            AttendanceArrear::where([
                "status" => "pending_approval",

            ])
                ->whereIn("attendance_id", $payroll_attendances_data->pluck("id"))
                ->update([
                    "status" => "completed",
                ]);
            LeaveRecordArrear::whereIn("leave_record_id", $payroll_leave_records_data->pluck("id"))
                ->update([
                    "status" => "completed",
                ]);

            $recalculate_payroll_values = $this->recalculate_payroll_values($payroll);
            if (!$recalculate_payroll_values) {
                throw new Exception("some thing went wrong");
            }
            $temp_payroll = clone $recalculate_payroll_values;


        } else {


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


        }
        return $temp_payroll;
    }
    public function generate_payroll_name($payrun)
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
            ->where("end_date", ">=", $attendance["in_date"])
            ->exists();

        if ($last_payroll_exists) {
            AttendanceArrear::create([
                "attendance_id" => $attendance->id,
                "status" => $add_to_next_payroll ? "approved" : "pending_approval",

            ]);
        }


    }
    public function adjust_payroll_on_attendance_update($attendance, $add_to_next_payroll = 0)
    {

        $attendance_arrear = AttendanceArrear::where(["attendance_id" => $attendance->id])->first();
        $payroll = Payroll::whereHas("payroll_attendances", function ($query) use ($attendance) {
            $query->where("payroll_attendances.attendance_id", $attendance->id);
        })->first();



        if (!$payroll) {
            if (!$attendance_arrear) {
                if ($attendance->status == "approved" || $attendance->total_paid_hours > 0) {
                    $this->create_attendance_arrear($attendance, $add_to_next_payroll);
                }
            } else {
                if ($attendance->status == "rejected") {
                    $attendance_arrear->delete();
                }
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
                $attendance_arrear->delete();
            }
            // if ($attendance_arrear) {
            //     $attendance_arrear->update([
            //         "status" => "pending_approval",
            //     ]);
            // } else {
            //     $this->create_attendance_arrear($attendance, 0);
            // }
        } else {
            if ($attendance_arrear) {
                $attendance_arrear->update([
                    "status" => "approved",
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
            ->where("end_date", ">=", $leave_record["date"])
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
        $leave_record_arrear = LeaveRecordArrear::where(["leave_record_id" => $leave_record->id])->first();

        $payroll = Payroll::whereHas("payroll_leave_records", function ($query) use ($leave_record) {
            $query->where("payroll_leave_records.leave_record_id", $leave_record->id);
        })->first();

        if (empty($payroll)) {
            if (empty($leave_record_arrear)) {
                if ($leave_record->leave->status == "approved" || $leave_record->leave->leave_type->type == "paid") {
                    $this->create_leave_arrear($leave_record, $add_to_next_payroll);

                }

            } else {
                if ($leave_record->leave->status == "rejected") {
                    $leave_record_arrear->delete();
                }

            }
            return true;
        }

        if ($leave_record->leave->status != "approved" || $leave_record->leave->leave_type->type != "paid") {
            PayrollLeaveRecord::where([
                "leave_record_id" => $leave_record->id,
                "payroll_id" => $payroll->id
            ])
                ->delete();



            if ($leave_record_arrear) {
                $leave_record_arrear->delete();
            }

            // if ($leave_record_arrear) {
            //     $leave_record_arrear->update([
            //         "status" =>  "pending_approval",
            //     ]);
            // } else {
            //     $this->create_leave_arrear($leave_record, 0);
            // }


        } else {
            if ($leave_record_arrear) {
                $leave_record_arrear->update([
                    "status" => "approved",
                ]);
            }
        }



        $this->recalculate_payroll_values($payroll);

        return true;
    }


    public function adjust_payroll_on_leave_update_v2($leave_record, $add_to_next_payroll = 0, )
    {

        $leave_record_arrear = LeaveRecordArrear::where(["leave_record_id" => $leave_record->id])->first();

        $payroll = Payroll::whereHas("payroll_leave_records", function ($query) use ($leave_record) {
            $query->where("payroll_leave_records.leave_record_id", $leave_record->id);
        })->first();

        if (empty($payroll)) {
            if (empty($leave_record_arrear)) {
                if ($leave_record->leave->status == "approved" || $leave_record->leave->leave_type->type == "paid") {
                    $this->create_leave_arrear($leave_record, $add_to_next_payroll);

                }

            } else {
                if ($leave_record->leave->status == "rejected") {
                    $leave_record_arrear->delete();
                }

            }
            return true;
        }

        if ($leave_record->leave->status != "approved" || $leave_record->leave->leave_type->type != "paid") {
            PayrollLeaveRecord::where([
                "leave_record_id" => $leave_record->id,
                "payroll_id" => $payroll->id
            ])
                ->delete();



            if ($leave_record_arrear) {
                $leave_record_arrear->delete();
            }

            // if ($leave_record_arrear) {
            //     $leave_record_arrear->update([
            //         "status" =>  "pending_approval",
            //     ]);
            // } else {
            //     $this->create_leave_arrear($leave_record, 0);
            // }


        } else {
            if ($leave_record_arrear) {
                $leave_record_arrear->update([
                    "status" => "approved",
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
        if (empty($payroll)) {
            return NULL;
        }

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

        $payroll->regular_hours = $payroll->total_holiday_hours + $payroll->total_paid_leave_hours + $payroll->total_regular_attendance_hours;
        $payroll->regular_hours_salary = $payroll->total_holiday_hours_salary + $payroll->leave_hours_salary + $regular_attendance_hours_salary;
        $payroll->overtime_hours_salary = $overtime_attendance_hours_salary;
        $payroll->regular_attendance_hours_salary = $regular_attendance_hours_salary;
        $payroll->overtime_attendance_hours_salary = $overtime_attendance_hours_salary;

        $payroll->save();
        return $payroll;
    }


    public function recalculate_payroll_values_v2($payroll_data,$attendances,$leave_records,$holidays)
    {
        if (!empty($holidays)) {
            $total_holiday_hours = 0;
            $total_holiday_hours_salary = 0;

            foreach ($holidays as $holiday) {
                $total_holiday_hours += $holiday["hours"];
                $total_holiday_hours_salary += ($holiday["hours"] * $holiday["hourly_salary"]);
            }
            $payroll_data["total_holiday_hours"] = $total_holiday_hours;
            $payroll_data["total_holiday_hours_salary"] = $total_holiday_hours_salary;
        } else {
            // Set total_paid_leave_hours to 0 if payroll_leave_records is empty
            $payroll_data["total_holiday_hours"] = 0;
            $payroll_data["total_holiday_hours_salary"] = 0;
        }


        if (!empty($leave_records)) {
            $total_paid_leave_hours = 0;
            $leave_hours_salary = 0;
            foreach ($leave_records as $leave_record) {
                if ($leave_record && $leave_record->leave) {
                        // Add leave hours to $total_paid_leave_hours
                        $total_paid_leave_hours += $leave_record->leave_hours;
                        $leave_hours_salary += $leave_record->leave->hourly_rate;
                }
            }
            $payroll_data["total_paid_leave_hours"] = $total_paid_leave_hours;
            $payroll_data["leave_hours_salary"] = $leave_hours_salary;
        } else {
            // Set total_paid_leave_hours to 0 if payroll_leave_records is empty
            $payroll_data["total_paid_leave_hours"] = 0;
            $payroll_data["leave_hours_salary"] = 0;
        }

        $total_attendance_salary = 0;
        $regular_attendance_hours_salary = 0;
        $overtime_attendance_hours_salary = 0;
        if (!empty($attendances)) {
            $total_regular_attendance_hours = 0;
            $overtime_hours = 0;

            foreach ($attendances as $attendance) {
                if ($attendance) {
                    $total_regular_attendance_hours += $attendance->regular_work_hours;
                    $overtime_hours += $attendance->overtime_hours;
                    $total_attendance_salary += ($attendance->regular_hours_salary + $attendance->overtime_hours_salary);
                    $regular_attendance_hours_salary += $attendance->regular_hours_salary;
                    $overtime_attendance_hours_salary += $attendance->overtime_hours_salary;
                }
            }

            $payroll_data["total_regular_attendance_hours"] = $total_regular_attendance_hours;
            $payroll_data["overtime_hours"] = $overtime_hours;
        } else {
            // Set both total_regular_attendance_hours and overtime_hours to 0 if payroll_attendances is empty
            $payroll_data["total_regular_attendance_hours"] = 0;
            $payroll_data["overtime_hours"] = 0;
        }

        $payroll_data["regular_hours"] = $payroll_data["total_holiday_hours"] + $payroll_data["total_paid_leave_hours"] + $payroll_data["total_regular_attendance_hours"];
        $payroll_data["regular_hours_salary"] = $payroll_data["total_holiday_hours_salary"] + $payroll_data["leave_hours_salary"] + $regular_attendance_hours_salary;
        $payroll_data["overtime_hours_salary"] = $overtime_attendance_hours_salary;
        $payroll_data["regular_attendance_hours_salary"] = $regular_attendance_hours_salary;
        $payroll_data["overtime_attendance_hours_salary"] = $overtime_attendance_hours_salary;


        return $payroll_data;
    }






}
