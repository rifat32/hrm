<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        "description",
        'attendances_count',
        'is_business_default',
        'is_personal',
        'start_date',
        'end_date',
        "is_active",
        "business_id",
        "created_by"

    ];


    public function details(){
        return $this->hasMany(WorkShiftDetail::class,'work_shift_id', 'id');
    }

    public function departments() {
        return $this->belongsToMany(Department::class, 'department_work_shifts', 'work_shift_id', 'department_id');
    }
    public function users() {
        return $this->belongsToMany(User::class, 'user_work_shifts', 'work_shift_id', 'user_id');
    }
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getStartDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getEndDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
}
