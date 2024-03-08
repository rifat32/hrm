<?php

namespace App\Http\Components;

use App\Models\Leave;
use Carbon\Carbon;
use Exception;

class LeaveComponent
{

    protected $authorizationComponent;
    protected $leaveComponent;
    protected $departmentComponent;
    protected $workShiftHistoryComponent;
    protected $holidayComponent;
    protected $attendanceComponent;

    public function __construct(AuthorizationComponent $authorizationComponent, LeaveComponent $leaveComponent, DepartmentComponent $departmentComponent, WorkShiftHistoryComponent $workShiftHistoryComponent, HolidayComponent $holidayComponent, AttendanceComponent $attendanceComponent)
    {
        $this->authorizationComponent = $authorizationComponent;
        $this->leaveComponent = $leaveComponent;
        $this->departmentComponent = $departmentComponent;
        $this->workShiftHistoryComponent = $workShiftHistoryComponent;
        $this->holidayComponent = $holidayComponent;
        $this->attendanceComponent = $attendanceComponent;
    }
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

    public function findLeave($leave_id = NULL ,$user_id, $date)
    {
        $leave =    Leave::where([
            "user_id" => $user_id
        ])
        ->when(!empty($leave_id), function($query) use($leave_id) {
            $query->whereNotIn("id",[$leave_id]);
        })
            ->whereHas('records', function ($query) use ($date) {
                $query->where('leave_records.date', ($date));
            })->first();
        return $leave;
    }

    public function getLeaveRecordDataItem(
        $work_shift_details,
        $holiday,
        $previous_leave,
        $previous_attendance,
        $date,
        $leave_duration,
        $day_type = "",
        $start_time="",
        $end_time=""
        ) {
             // Check if it's feasible to take leave
        if ((!$work_shift_details->is_weekend && (!$holiday || !$holiday->is_active) && !$previous_leave && !$previous_attendance)) {
              // Convert shift times to Carbon instances
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
             // Use specified start and end times for leave
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
// Check for conditions preventing leave
        if($leave_duration != "multiple_day") {
            if($work_shift_details->is_weekend) {
                 throw new Exception(("there is a weekend on date " . $date));
            }
            if($holiday && $holiday->is_active) {
                throw new Exception(("there is a holiday on date " . $date));
            }
            if($previous_leave) {
                throw new Exception(("there is a leave exists on date " . $date));
            }
            if($previous_attendance) {
                throw new Exception(("there is an attendance exists on date " . $date));
            }
        }

        return [];

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



public function generateLeaveDates ($start_date,$end_date) {
    $start_date = Carbon::parse($start_date);
    $end_date = Carbon::parse($end_date);
    $leave_dates = [];
    for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
        $leave_dates[] = $date->format('Y-m-d');
    }
    return $leave_dates;
}





public function processLeave($leave_data,$leave_date,$all_parent_department_ids,&$leave_record_data_list) {
  // Retrieve work shift history for the user and date
  $work_shift_history =  $this->workShiftHistoryComponent->get_work_shift_history($leave_date, $leave_data["user_id"]);
  // Retrieve work shift details based on work shift history and date
  $work_shift_details =  $this->workShiftHistoryComponent->get_work_shift_details($work_shift_history, $leave_date);
  // Retrieve holiday based on date and user id
  $holiday = $this->holidayComponent->get_holiday_details($leave_date, $leave_data["user_id"], $all_parent_department_ids);

  $previous_leave = $this->findLeave(
 (!empty($leave_data["id"])?$leave_data["id"]:NULL),
  $leave_data["user_id"],
  $leave_date);

  $previous_attendance = $this->attendanceComponent->checkAttendanceExists(NULL,$leave_data["user_id"],$leave_date);


if($leave_data["leave_duration"] == "hours") {
    $this->validateLeaveTimes($work_shift_details,$leave_data["start_time"],$leave_data["end_time"]);
}

  $leave_record_data_item = $this->getLeaveRecordDataItem(
      $work_shift_details,
      $holiday,
      $previous_leave,
      $previous_attendance,
      $leave_date,
      $leave_data["leave_duration"],
      $leave_data["day_type"],
      $leave_data["start_time"],
      $leave_data["end_time"]
  );
  if (!empty($leave_record_data_item)) {
      array_push($leave_record_data_list, $leave_record_data_item);
  }

}


public function processLeaveRequest($raw_data) {


    $leave_data =  !empty($raw_data["id"])?$raw_data:$this->prepare_data_on_leave_create($raw_data, $raw_data["user_id"]);

    $leave_record_data_list = [];
    $all_parent_department_ids = $this->departmentComponent->all_parent_departments_of_user($leave_data["user_id"]);

    switch ($leave_data["leave_duration"]) {
        case "multiple_day":
            $leave_dates = $this->generateLeaveDates($leave_data["start_date"],$leave_data["end_date"]);
            foreach ($leave_dates as $leave_date) {
                $this->processLeave($leave_data,$leave_date,$all_parent_department_ids,$leave_record_data_list);
            }
            break;

        case "single_day":
        case "half_day":
        case "hours":
        $leave_data["start_date"] = Carbon::parse($leave_data["date"]);
        $leave_data["end_date"] = Carbon::parse($leave_data["date"]);
        $this->processLeave($leave_data,$leave_data["date"],$all_parent_department_ids,$leave_record_data_list);
            break;

        default:
            // Handle unsupported leave duration type
            break;
    }

    return [
        "leave_data" => $leave_data,
        "leave_record_data_list" => $leave_record_data_list
    ];

}


}
