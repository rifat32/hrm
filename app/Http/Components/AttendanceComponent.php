<?php
namespace App\Http\Components;

use App\Models\Attendance;

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
}
