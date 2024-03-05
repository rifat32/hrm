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


    public function get_work_shift_history($request_data)
    {
        $work_shift_history =  WorkShiftHistory::where("from_date", "<", $request_data["in_date"])
            ->where(function ($query) use ($request_data) {
                $query->where("to_date", ">=", $request_data["in_date"])
                    ->orWhereNull("to_date");
            })
            ->whereHas("users", function ($query) use ($request_data) {
                $query->where("users.id", $request_data["user_id"])
                    ->where("employee_user_work_shift_histories.from_date", "<", $request_data["in_date"])
                    ->where(function ($query) use ($request_data) {
                        $query->where("employee_user_work_shift_histories.to_date", ">=", $request_data["in_date"])
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

    public function get_holiday_details($in_date)
    {
        $all_parent_department_ids = [];
        $assigned_departments = Department::whereHas("users", function ($query) use ($request_data) {
            $query->where("users.id", $request_data["user_id"]);
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
            ->where(function ($query) use ($request_data, $all_parent_department_ids) {
                $query->whereHas("users", function ($query) use ($request_data) {
                    $query->where([
                        "users.id" => $request_data["user_id"]
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
}
