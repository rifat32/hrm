<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'salary_range',
        'required_skills',
        'application_deadline',
        'posted_on',
        'job_platform_id',
        'department_id',

        "is_active",
        "business_id",
        "created_by"

    ];

    // Define relationships if needed
    public function job_platform()
    {
        return $this->belongsTo(JobPlatform::class, 'job_platform_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
