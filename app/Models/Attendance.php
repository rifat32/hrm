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
        'employee_id',
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




        "is_active",
        "business_id",
        "created_by"
    ];

    public function employee(){
        return $this->hasOne(User::class,'id', 'employee_id');
    }


    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }





    public function getInDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }



}
