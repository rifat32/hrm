<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingLeaveType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'type',
        'amount',
        'description',
        'is_earning_enabled',
        "is_active",
        "is_default",
        "business_id",
        "created_by"
    ];
}
