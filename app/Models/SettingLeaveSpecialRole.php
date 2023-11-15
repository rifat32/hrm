<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingLeaveSpecialRole extends Model
{
    use HasFactory;
    protected $fillable = [
        'setting_leave_id', 'role_id'
    ];
}
