<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRecord extends Model
{
    use HasFactory;
    protected $fillable = [
        'leave_id',
        'date',
        'start_time',
        'end_time',

    ];
    public function getDurationAttribute()
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        // Calculate the difference in hours
        return $startTime->diffInHours($endTime);
    }
    public function leave(){
        return $this->belongsTo(Leave::class,'leave_id', 'id');
    }

}
