<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'note',
        'employee_id',
        'in_time',
        'out_time',
        'in_date',
        "is_active",
        "business_id",
        "created_by"
    ];
}
