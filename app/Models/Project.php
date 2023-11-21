<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'department_id',

        "is_active",
        "business_id",
        "created_by"
    ];

    // Assuming you have a relationship with the Department model
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
