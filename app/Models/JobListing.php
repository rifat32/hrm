<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function job_platforms() {
        return $this->belongsToMany(JobListingJobPlatforms::class, 'job_listing_job_platforms', 'job_listing_id', 'job_platform_id');
    }


    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }




    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }







    public function getApplicationDeadlineAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getPostedOnAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }

}
