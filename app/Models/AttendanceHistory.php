<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        "attendance_id",
        "actor_id",
        "action",

        "attendance_created_at",
        "attendance_updated_at",




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
        "punch_in_time_tolerance",
        "status",
        'work_location_id',
        'project_id',
        "is_active",
        "business_id",
        "created_by",
        "regular_hours_salary",
        "overtime_hours_salary",
    ];

    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }




}
