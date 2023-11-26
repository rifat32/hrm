<?php

namespace App\Models;

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

}
