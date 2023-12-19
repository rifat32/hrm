<?php

namespace App\Models;

use Carbon\Carbon;
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
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
}
