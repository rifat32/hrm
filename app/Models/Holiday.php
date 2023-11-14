<?php

namespace App\Models;

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

}
