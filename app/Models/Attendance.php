<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'note',
        "in_geolocation",
        "out_geolocation",
        'user_id',
        'in_time',
        'out_time',
        'in_date',
        'does_break_taken',

        "behavior",
        "capacity_hours",
        "work_hours_delta",
        "break_type",
        "break_hours",
        "total_paid_hours",

        "regular_work_hours",

        "work_shift_start_at",
        "work_shift_end_at",
        "work_shift_history_id",
        "holiday_id",
        "leave_record_id",
        "is_weekend",
        "overtime_start_time",
        "overtime_end_time",
        "overtime_hours",
        "leave_start_time",
        "leave_end_time",



        "punch_in_time_tolerance",

        "leave_hours",
        "status",

        'work_location_id',
        'project_id',


        "is_active",
        "business_id",
        "created_by"
    ];

    public function arrear(){
        return $this->hasOne(AttendanceArrear::class,'attendance_id', 'id');
    }

    public function payroll_attendance()
    {
        return $this->hasOne(PayrollAttendance::class, "attendance_id" ,'id');
    }

    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }


    public function work_location()
    {
        return $this->belongsTo(WorkLocation::class, "work_location_id" ,'id');
    }


    public function project()
    {
        return $this->belongsTo(Project::class, "project_id" ,'id');
    }





}
