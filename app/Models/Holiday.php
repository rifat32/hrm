<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'description', 'start_date', 'end_date', 'repeats_annually',  'is_active', 'business_id', "created_by",
    ];
    public function departments() {
        return $this->belongsToMany(Department::class, 'department_holidays', 'holiday_id', 'department_id');
    }
    public function creator() {
        return $this->belongsTo(User::class, "created_by","id");
    }

    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getStartDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getEndDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }

}
