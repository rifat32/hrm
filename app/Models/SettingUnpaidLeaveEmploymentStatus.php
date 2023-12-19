<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingUnpaidLeaveEmploymentStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'setting_leave_id', 'employment_status_id'
    ];
    protected $table = "paid_leave_employment_statuses";
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
}
