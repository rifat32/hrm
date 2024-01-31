<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveArrear extends Model
{
    use HasFactory;
    protected $fillable = [
        'leave_id',
        "status",
    ];

    public function leave()
    {
        return $this->belongsTo(Leave::class, "leave_id" ,'id');
    }




    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
}
