<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShiftHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        "break_type",
        "break_hours",
        'type',
        "description",
        'attendances_count',
        'is_business_default',
        'is_personal',

"is_flexible",

        "is_default",
        "is_active",
        "business_id",
        "created_by",



        "from_date",
        "to_date",
        "work_shift_id",


    ];

    protected $dates = ['start_date',
    'end_date'];


    public function details(){
        return $this->hasMany(WorkShiftDetailHistory::class,'work_shift_id', 'id');
    }

    public function departments() {
        return $this->belongsToMany(Department::class, 'employee_department_work_shift_histories', 'work_shift_id', 'department_id');
    }


    public function users() {
        return $this->belongsToMany(User::class, 'employee_user_work_shift_histories', 'work_shift_id', 'user_id')->withPivot('from_date', 'to_date');
    }

    public function user_work_shift(){
        return $this->hasMany(EmployeeUserWorkShiftHistory::class,'work_shift_id', 'id');
    }




}
