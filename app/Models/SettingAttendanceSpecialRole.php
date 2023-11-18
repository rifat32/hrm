<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingAttendanceSpecialRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_attendance_id', 'role_id'
    ];
}
