<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    use HasFactory;
    protected $fillable = [
        'leave_id',
        'is_approved',
        "created_by"
    ];

}
