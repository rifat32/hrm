<?php

namespace App\Http\Utils;

use App\Models\Attendance;
use App\Models\Coupon;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

trait AttendanceUtil
{
    use PayrunUtil, BasicUtil;

    public function prepare_data_on_attendance_create($raw_data, $user_id)
    {

        $raw_data["user_id"] = $user_id;
        $raw_data["business_id"] = auth()->user()->business_id;
        $raw_data["is_active"] = true;
        $raw_data["created_by"] = auth()->user()->id;
        $raw_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");
        return $raw_data;
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


    public function get_work_shift_history($in_date, $user_id)
    {
        $work_shift_history =  WorkShiftHistory::where(function ($query) use ($in_date, $user_id) {
                $query->where("from_date", "<=", $in_date)
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
            ->orWhere(function ($query) {
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

    public function get_work_shift_details($work_shift_history, $in_date)
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

    public function get_holiday_details($in_date, $user_id, $all_parent_department_ids)
    {

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

    public function get_leave_record_details($in_date, $user_id, $in_time = "", $out_time = "")
    {
        $leave_record = LeaveRecord::whereHas('leave',    function ($query) use ($in_date, $user_id) {
            $query->whereIn("leaves.user_id",  [$user_id])
                ->where("leaves.status", "approved");
        })
            ->where('date', '>=', $in_date . ' 00:00:00')
            ->where('date', '<=', ($in_date . ' 23:59:59'))
            ->first();


        if ($leave_record) {
            if ($leave_record->leave->leave_duration == "hours") {
                $attendance_in_time = Carbon::parse($in_time);
                $attendance_out_time = Carbon::parse($out_time);

                $leave_start_time = Carbon::parse($leave_record->start_time);
                $leave_end_time = Carbon::parse($leave_record->end_time);

                $balance_start_time = $attendance_in_time->max($leave_start_time);
                $balance_end_time = $attendance_out_time->min($leave_end_time);

                if ($balance_start_time < $balance_end_time) {
                    throw new Exception(("there is an hourly leave on date" . $in_date), 400);
                }
            } else {
                throw new Exception(("there is a leave on date" . $in_date), 400);
            }
        }

        return $leave_record;
    }

    public function calculate_capacity_hours($work_shift_details)
    {
        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
        $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
        return $work_shift_end_at->diffInHours($work_shift_start_at);
    }

    public function calculate_total_present_hours($in_time, $out_time)
    {
        $in_time = Carbon::createFromFormat('H:i:s', $in_time);
        $out_time = Carbon::createFromFormat('H:i:s', $out_time);
        return $out_time->diffInHours($in_time);
    }

    function calculate_tolerance_time($in_time, $work_shift_details)
    {
        $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
        $in_time = Carbon::createFromFormat('H:i:s', $in_time);
        return $in_time->diffInHours($work_shift_start_at);
    }
    public function determine_behavior($tolerance_time, $setting_attendance)
    {
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

    function adjust_paid_hours($does_break_taken, $total_present_hours, $work_shift_history)
    {
        if ($does_break_taken) {
            if ($work_shift_history->break_type == 'unpaid') {
                return  $total_present_hours - $work_shift_history->break_hours;
            }
        }
        return $total_present_hours;
    }

    public function calculate_overtime($is_weekend, $work_hours_delta, $total_paid_hours, $leave_record, $holiday, $in_time, $out_time)
    {
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

    function calculate_regular_work_hours($total_paid_hours, $result_balance_hours)
    {
        return $total_paid_hours - $result_balance_hours;
    }







    public function process_attendance_data($raw_data, $setting_attendance, $user_id)
    {
        // Prepare data for attendance creation
        $attendance_data = $this->prepare_data_on_attendance_create($raw_data, $user_id);

        // Automatically approve attendance if auto-approval is enabled in settings
        if (
            (
            isset($setting_attendance->auto_approval) && $setting_attendance->auto_approval
            )
            ||
             auth()->user()->hasRole("business_owner")

             ) {
            $attendance_data["status"] = "approved";
        }

        // Retrieve salary information for the user and date
        $user_salary_info = $this->get_salary_info($user_id, $attendance_data["in_date"]);

        // Retrieve work shift history for the user and date
        $work_shift_history =  $this->get_work_shift_history($attendance_data["in_date"], $user_id);

        // Retrieve work shift details based on work shift history and date
        $work_shift_details =  $this->get_work_shift_details($work_shift_history, $attendance_data["in_date"]);

        // Retrieve holiday details for the user and date
        $all_parent_departments_of_user = $this->all_parent_departments_of_user($user_id);

        $holiday = $this->get_holiday_details($attendance_data["in_date"], $user_id, $all_parent_departments_of_user);

        // Retrieve leave record details for the user and date
        $leave_record = $this->get_leave_record_details($attendance_data["in_date"], $user_id, $attendance_data["in_time"], $attendance_data["out_time"]);

        // Calculate capacity hours based on work shift details
        $capacity_hours = $this->calculate_capacity_hours($work_shift_details);

        // Calculate total present hours based on in and out times
        $total_present_hours = $this->calculate_total_present_hours($attendance_data["in_time"], $attendance_data["out_time"]);

        // Calculate tolerance time based on in time and work shift details
        $tolerance_time = $this->calculate_tolerance_time($attendance_data["in_time"], $work_shift_details);

        // Determine behavior based on tolerance time and attendance setting
        $behavior = $this->determine_behavior($tolerance_time, $setting_attendance);

        // Adjust paid hours based on break taken and work shift history
        $total_paid_hours = $this->adjust_paid_hours($attendance_data["does_break_taken"], $total_present_hours, $work_shift_history);

        // Calculate work hours delta
        $work_hours_delta = $total_present_hours - $capacity_hours;

        // Calculate overtime information
        $overtime_information = $this->calculate_overtime($work_shift_details->is_weekend, $work_hours_delta, $total_paid_hours, $leave_record, $holiday, $attendance_data["in_time"], $attendance_data["out_time"]);

        // Calculate regular work hours
        $regular_work_hours = $this->calculate_regular_work_hours($total_paid_hours, $overtime_information["overtime_hours"]);


        $attendance_data["break_type"] = $work_shift_history->break_type;
        $attendance_data["break_hours"] = $work_shift_history->break_hours;
        $attendance_data["behavior"] = $behavior;
        $attendance_data["capacity_hours"] = $capacity_hours;
        $attendance_data["work_hours_delta"] = $work_hours_delta;
        $attendance_data["total_paid_hours"] = $total_paid_hours;
        $attendance_data["regular_work_hours"] = $regular_work_hours;
        $attendance_data["work_shift_start_at"] = $work_shift_details->start_at;
        $attendance_data["work_shift_end_at"] =  $work_shift_details->end_at;
        $attendance_data["work_shift_history_id"] = $work_shift_history->id;
        $attendance_data["holiday_id"] = $holiday ? $holiday->id : NULL;
        $attendance_data["leave_record_id"] = $leave_record ? $leave_record->id : NULL;
        $attendance_data["is_weekend"] = $work_shift_details->is_weekend;
        $attendance_data["overtime_start_time"] = $overtime_information["overtime_start_time"];
        $attendance_data["overtime_end_time"] = $overtime_information["overtime_end_time"];
        $attendance_data["overtime_hours"] = $overtime_information["overtime_hours"];
        $attendance_data["punch_in_time_tolerance"] = $setting_attendance->punch_in_time_tolerance;
        $attendance_data["regular_hours_salary"] =   $regular_work_hours * $user_salary_info["hourly_salary"];
        $attendance_data["overtime_hours_salary"] =   $overtime_information["overtime_hours"] * $user_salary_info["overtime_salary_per_hour"];

        return $attendance_data;
    }


    public function is_special_user($user, $setting_attendance)
    {
        return $setting_attendance
            ->special_users()
            ->where(["setting_attendance_special_users.user_id" => $user->id])
            ->first();
    }

    public function is_special_role($user, $setting_attendance)
    {
        $role_names = $user->getRoleNames()->toArray();
        $roles = Role::whereIn("name", $role_names)->get();

        foreach ($roles as $role) {
            $special_role = $setting_attendance->special_roles()->where(["role_id" => $role->id])->first();
            if ($special_role) {
                return true;
            }
        }
        return false;
    }

    public function find_attendance($attendance_query_params)
    {
        $attendance =  Attendance::where($attendance_query_params)->first();
        if (!$attendance) {
            throw new Exception("Some thing went wrong");
        }
        return $attendance;
    }

    public function calculate_behavior_counts($attendances)
    {
        return [
            'absent' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'absent')->count(),
            'regular' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'regular')->count(),
            'early' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'early')->count(),
            'late' => $attendances->filter(fn ($attendance) => $attendance->behavior === 'late')->count(),
        ];
    }

    public function calculate_max_behavior($behaviorCounts)
    {
        return max($behaviorCounts);
    }


}
