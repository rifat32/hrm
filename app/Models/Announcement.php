<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'description',
        "is_active",
        "business_id",
        "created_by"
    ];
    public function creator() {
        return $this->belongsTo(User::class, "created_by","id");
    }

    public function departments() {
        return $this->belongsToMany(Department::class, 'department_announcements', 'announcement_id', 'department_id');
    }
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d/m/Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d/m/Y');
    }
    public function getStartDateAttribute($value)
    {
        return (new Carbon($value))->format('d/m/Y');
    }
    public function getEndDateAttribute($value)
    {
        return (new Carbon($value))->format('d/m/Y');
    }
}
