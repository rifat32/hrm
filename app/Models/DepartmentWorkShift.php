<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentWorkShift extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_shift_id', 'department_id'
    ];

}
