<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payrun extends Model
{
    use HasFactory;

    protected $fillable = [
        "period_type",
        "start_date",
        "end_date",
        "generating_type",
        "consider_type",
        "consider_overtime",
        "notes",

        "is_active",
        "business_id",
        "created_by"
    ];

    public function departments() {
        return $this->belongsToMany(Department::class, 'payrun_departments', 'payrun_id', 'department_id');
    }
    public function users() {
        return $this->belongsToMany(User::class, 'payrun_users', 'payrun_id', 'user_id');
    }


    public function getStartDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getEndDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }

    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
}
