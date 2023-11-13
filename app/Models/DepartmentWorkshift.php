<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentWorkshift extends Model
{
    use HasFactory;
    protected $fillable = [
        'department_id', 'work_shift_id'
    ];

}
