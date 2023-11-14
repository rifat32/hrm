<?php

namespace App\Models;

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

    public function departments() {
        return $this->belongsToMany(Department::class, 'department_announcements', 'announcement_id', 'department_id');
    }
}
