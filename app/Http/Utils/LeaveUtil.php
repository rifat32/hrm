<?php

namespace App\Http\Utils;

use App\Models\ErrorLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

trait LeaveUtil
{
   public function getWorkShiftDetails($work_shift, $date) {
        $dayNumber = Carbon::parse($date)->dayOfWeek;
        return $work_shift->details()->where([
            "off_day" => $dayNumber
        ])->first();
    }
    public function addLeaveRecordData($start_time, $end_time, $date, &$leave_record_data_list) {
        $leave_record_data["start_time"] = $start_time;
        $leave_record_data["end_time"] = $end_time;
        $leave_record_data["date"] = $date;
        array_push($leave_record_data_list, $leave_record_data);
    }
}
