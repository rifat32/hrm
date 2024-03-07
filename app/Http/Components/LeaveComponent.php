<?php

namespace App\Http\Components;

use App\Models\Leave;
use Carbon\Carbon;
use Exception;

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

    public function getLeaveRecordDataItem(
        $work_shift_details,
        $holiday,
        $previous_leave,
        $date,
        $leave_duration,
        $day_type = "",
        $start_time="",
        $end_time=""
        ) {
        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave)) {
        $leave_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
        $leave_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);

        // Calculate capacity hours based on work shift details
        $capacity_hours = $leave_end_at->diffInHours($leave_start_at);



// Adjust leave hours based on the type of leave
        if($leave_duration == "half_day") {
            if ($day_type == "first_half") {
                // For first half-day leave, adjust the end time
                $leave_end_at = $leave_start_at->copy()->addHours($capacity_hours / 2);
            } else if ($day_type == "last_half") {
                // For last half-day leave, adjust the start time
                $leave_start_at = $leave_end_at->copy()->subHours($capacity_hours / 2);
            }
        }
        else if($leave_duration == "hours") {
            $leave_start_at = Carbon::createFromFormat('H:i:s', $start_time);
            $leave_end_at = Carbon::createFromFormat('H:i:s', $end_time);
        }

        // Calculate leave hours based on adjusted start and end times
        $leave_hours = $leave_end_at->diffInHours($leave_start_at);

        // Prepare leave record data
        $leave_record_data["leave_hours"] =  $leave_hours;
        $leave_record_data["capacity_hours"] =  $capacity_hours;
        $leave_record_data["start_time"] = $work_shift_details->start_at;
        $leave_record_data["end_time"] = $work_shift_details->end_at;
        $leave_record_data["date"] = $date;
        return $leave_record_data;
        }
        return [];

    }

  // Function to handle processing of leave record data
function processLeaveRecord($date, $work_shift_details, $holiday, $previous_leave, $leave_data, &$leave_record_data_list) {

    $leave_record_data_list = [];

    $leave_record_data_item = $this->getLeaveRecordDataItem(
        $work_shift_details,
        $holiday,
        $previous_leave,
        $date,
        $leave_data["leave_duration"],
        $leave_data["day_type"],
        $leave_data["start_time"] ?? null,
        $leave_data["end_time"] ?? null
    );
    if (!empty($leave_record_data_item)) {
        array_push($leave_record_data_list, $leave_record_data_item);
    }
}

public function validateLeaveTimes($workShiftDetails,$start_time,$end_time){

    $start_time = Carbon::parse($start_time);
    $end_time = Carbon::parse($end_time);

    $workShiftStart = Carbon::parse($workShiftDetails->start_at);
    $workShiftEnd = Carbon::parse($workShiftDetails->end_at);

    if ($start_time->lt($workShiftStart)) {
        throw new Exception(
            "Employee does not start working at $start_time. Starts at " . $workShiftDetails->start_at,
            400
        );
    }

    if ($end_time->gt($workShiftEnd)) {
        throw new Exception("Employee does not close working at $end_time", 400);
    }
}





}
