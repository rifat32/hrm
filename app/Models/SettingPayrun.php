<?php

namespace App\Models;

use Carbon\Carbon;
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
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
}
