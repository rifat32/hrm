<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    protected $fillable = [
        'leave_duration',
        'day_type',
        'leave_type_id',
        'user_id',
        'date',
        'note',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'attachments',
        "status",
        "is_active",
        "business_id",
        "created_by",
    ];


    public function records(){
        return $this->hasMany(LeaveRecord::class,'leave_id', 'id');
    }
    public function employee() {
        return $this->belongsTo(User::class, "user_id","id");
    }
    public function leave_type() {
        return $this->belongsTo(SettingLeaveType::class, "leave_type_id","id");
    }
    protected $casts = [
        'attachments' => 'array',

    ];




    // public function getCreatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
    // public function getUpdatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }



    // public function getDateAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }

    // public function getStartDateAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
    // public function getEndDateAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }







}
