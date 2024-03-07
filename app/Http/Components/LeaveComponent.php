<?php

namespace App\Http\Components;

use App\Models\Leave;
use Carbon\Carbon;

class LeaveComponent
{
    public function prepare_data_on_leave_create($raw_data, $user_id)
    {
        $raw_data["user_id"] = $user_id;
        $raw_data["business_id"] = auth()->user()->business_id;
        $raw_data["is_active"] = true;
        $raw_data["created_by"] = auth()->user()->id;
        $raw_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");


        return $raw_data;
    }

    public function get_leave_start_date($raw_data)
    {
        if ($raw_data["leave_duration"] == "multiple_day") {
            $work_shift_start_date = $raw_data["start_date"];
        } else {
            $work_shift_start_date = $raw_data["date"];
        }
    }

    public function findLeave($user_id, $date)
    {
        $leave =    Leave::where([
            "user_id" => $user_id
        ])
            ->whereHas('records', function ($query) use ($date) {
                $query->where('leave_records.date', ($date));
            })->first();
        return $leave;
    }

    public function getLeaveRecordDataItem($work_shift_details,$holiday,$previous_leave,$date) {
        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave)) {
            $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
            $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);
            $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
            $leave_record_data["leave_hours"] =  $capacity_hours;
            $leave_record_data["capacity_hours"] =  $capacity_hours;
            $leave_record_data["start_time"] = $work_shift_details->start_at;
            $leave_record_data["end_time"] = $work_shift_details->end_at;
            $leave_record_data["date"] = ($date);
            return $leave_record_data;
        }
        return [];

    }
}
