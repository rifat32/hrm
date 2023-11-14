<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'departments',

        'users',
        'attendances_count',
        'details',
        'start_date',
        'end_date',
        "is_active",
        "business_id",
        "created_by"

    ];
    protected $casts = [

        'details' => 'array',
    ];

    public function departments() {
        return $this->belongsToMany(Department::class, 'department_work_shifts', 'work_shift_id', 'department_id');
    }
    public function users() {
        return $this->belongsToMany(Department::class, 'department_work_shifts', 'work_shift_id', 'user_id');
    }
}