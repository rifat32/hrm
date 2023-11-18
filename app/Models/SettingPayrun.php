<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingPayrun extends Model
{
    use HasFactory;
    protected $fillable = [
        'payrun_period',
        'consider_type',
        'consider_overtime',

        "business_id",
        "is_active",
        "is_default",
        "created_by"
    ];
}
