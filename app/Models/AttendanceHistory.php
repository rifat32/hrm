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
        "holiday_id",
        "leave_record_id",
        "is_weekend",
        "overtime_start_time",
        "overtime_end_time",

        "overtime_hours",

        "leave_hours",

        "leave_start_time",
        "leave_end_time",
        "status",



        "is_active",
        "business_id",
        "created_by"
    ];

    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }




}
