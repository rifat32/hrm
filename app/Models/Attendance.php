<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'note',
        'employee_id',
        'in_time',
        'out_time',
        'in_date',
        "is_active",
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

    public function getInDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }



}
