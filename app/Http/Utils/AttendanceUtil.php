<?php

namespace App\Http\Utils;

use App\Models\Coupon;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\SettingAttendance;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

trait AttendanceUtil
{


    public function prepare_request_on_attendance_create($request)
    {
        $request_data = $request->validated();
        $request_data["business_id"] = $request->user()->business_id;
        $request_data["is_active"] = true;
        $request_data["created_by"] = $request->user()->id;
        $request_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");
        return $$request_data;
    }


    public function get_attendance_setting()
    {
        $setting_attendance = SettingAttendance::where([
            "business_id" => auth()->user()->business_id
        ])
            ->first();
        if (!$setting_attendance) {
            throw new Exception("Please define attendance setting first", 400);
        }
        return $setting_attendance;
    }


    public function get_work_shift_history($in_date,$user_id)
    {
        $work_shift_history =  WorkShiftHistory::where("from_date", "<", $in_date)
            ->where(function ($query) use ($in_date) {
                $query->where("to_date", ">=", $in_date)
                    ->orWhereNull("to_date");
            })
            ->whereHas("users", function ($query) use ($in_date,$user_id) {
                $query->where("users.id", $user_id)
                    ->where("employee_user_work_shift_histories.from_date", "<", $in_date)
                    ->where(function ($query) use ($in_date) {
                        $query->where("employee_user_work_shift_histories.to_date", ">=", $in_date)
                            ->orWhereNull("employee_user_work_shift_histories.to_date");
                    });
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

    public function get_holiday_details($in_date,$user_id)
    {
        $all_parent_department_ids = [];
        $assigned_departments = Department::whereHas("users", function ($query) use ($user_id) {
            $query->where("users.id", $user_id);
        })->get();


        foreach ($assigned_departments as $assigned_department) {
            array_push($all_parent_department_ids, $assigned_department->id);
            $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
        }

        $holiday =   Holiday::where([
            "business_id" => auth()->user()->business_id
        ])
            ->where('holidays.start_date', "<=", $in_date)
            ->where('holidays.end_date', ">=", $in_date . ' 23:59:59')
            ->where(function ($query) use ($user_id, $all_parent_department_ids) {
                $query->whereHas("users", function ($query) use ($user_id) {
                    $query->where([
                        "users.id" => $user_id
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
            ->first();

        // if ($holiday && $holiday->is_active && !auth()->user()->hasRole("business_owner")) {
        //         throw new Exception(("there is a holiday on date" . $in_date), 400);
        // }

        return $holiday;
    }

    public function get_leave_record_details($in_date,$user_id) {
        $leave_record = LeaveRecord::whereHas('leave',    function ($query) use ($in_date, $user_id) {
            $query->whereIn("leaves.user_id",  [$user_id])
                ->where("leaves.status", "approved");
        })
            ->where('date', '>=', $in_date . ' 00:00:00')
            ->where('date', '<=', ($in_date. ' 23:59:59'))
            ->first();


        // if ($leave_record && !auth()->user()->hasRole("business_owner")) {
        //     throw new Exception(("there is a leave on date" . $in_date), 400);
        // }

        return $leave_record;
    }

    public function calculate_capacity_hours($work_shift_details) {
        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
        $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
        return $work_shift_end_at->diffInHours($work_shift_start_at);
    }

    public function calculate_total_present_hours($in_time, $out_time) {
        $in_time = Carbon::createFromFormat('H:i:s', $in_time);
        $out_time = Carbon::createFromFormat('H:i:s', $out_time);
        return $out_time->diffInHours($in_time);
    }

    function calculate_tolerance_time($in_time, $work_shift_details) {
        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
        $in_time = Carbon::createFromFormat('H:i:s', $in_time);
        return $in_time->diffInHours($work_shift_start_at);
    }
    public function determine_behavior($tolerance_time, $setting_attendance){
        if (empty($setting_attendance->punch_in_time_tolerance)) {
            return  "regular";
        } else {
            if ($tolerance_time > $setting_attendance->punch_in_time_tolerance) {
                return "late";
            } else if ($tolerance_time < (-$setting_attendance->punch_in_time_tolerance)) {
                return "early";
            } else {
                return "regular";
            }
        }
    }

    function adjust_paid_hours($does_break_taken,$total_present_hours, $work_shift_history) {
        if ($does_break_taken) {
            if ($work_shift_history->break_type == 'unpaid') {
              return  $total_present_hours - $work_shift_history->break_hours;
            }
        }
        return $total_present_hours;

    }

    public function calculate_overtime($is_weekend,$work_hours_delta, $total_paid_hours, $leave_record, $holiday, $in_time,$out_time) {
        $overtime_start_time = NULL;
        $overtime_end_time = NULL;
        $overtime_hours = 0;

        if ($is_weekend || $holiday) {
            $overtime_start_time = $in_time;
            $overtime_end_time = $out_time;
            $overtime_hours = $total_paid_hours;
        } else if ($leave_record) {

            $attendance_in_time = Carbon::parse($in_time);
            $attendance_out_time = Carbon::parse($out_time);

            $leave_start_time = Carbon::parse($leave_record->start_time);
            $leave_end_time = Carbon::parse($leave_record->end_time);

            $balance_start_time = $attendance_in_time->max($leave_start_time);
            $balance_end_time = $attendance_out_time->min($leave_end_time);

            if ($balance_start_time < $balance_end_time) {
                $overtime_hours = $balance_start_time->diffInHours($balance_end_time);
                $overtime_start_time = $balance_start_time;
                $overtime_end_time = $balance_end_time;
            }
        } else if ($work_hours_delta > 0) {
            $overtime_hours = $work_hours_delta;
        }
        return [
            "overtime_start_time" => $overtime_start_time,
            "overtime_end_time" => $overtime_end_time,
            "overtime_hours" => $overtime_hours
        ];


    }

    function calculate_regular_work_hours($total_paid_hours, $result_balance_hours) {
        return $total_paid_hours - $result_balance_hours;
    }
}
