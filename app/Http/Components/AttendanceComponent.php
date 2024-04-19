<?php
namespace App\Http\Components;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceComponent {


    public function checkAttendanceExists($id="",$user_id,$date) {
        $exists = Attendance::when(!empty($id), function($query) use($id) {
            $query->whereNotIn('id', [$id]);
        })
            ->where('attendances.user_id', $user_id)
            ->where('attendances.in_date', $date)
            ->where('attendances.business_id', auth()->user()->business_id)
            ->exists(); ;
        return $exists;
    }



    public function get_already_taken_attendance_dates($user_id,$start_date,$end_date) {
        $already_taken_attendances =  Attendance::where([
            "user_id" => $user_id
        ])
            ->where('attendances.in_date', '>=', $start_date)
            ->where('attendances.in_date', '<=', $end_date . ' 23:59:59')
            ->get();



        $already_taken_attendance_dates = $already_taken_attendances->map(function ($attendance) {
            return Carbon::parse($attendance->in_date)->format('d-m-Y');
        });

        return $already_taken_attendance_dates;
    }




}
