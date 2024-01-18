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
        'required_skills',
        'application_deadline',
        'posted_on',
        'department_id',
        'minimum_salary',
        'maximum_salary',
        'experience_level',
        'job_type_id',
        'work_location_id',
        "is_active",
        "business_id",
        "created_by"

    ];



    // Define relationships with other tables
    public function job_type()
    {
        return $this->belongsTo(JobType::class, "job_type_id",'id');
    }

    public function work_location()
    {
        return $this->belongsTo(WorkLocation::class, "work_location_id" ,'id');
    }


    // Define relationships if needed

    public function job_platforms() {
        return $this->belongsToMany(JobPlatform::class, 'job_listing_job_platforms', 'job_listing_id', 'job_platform_id');
    }


    public function department()
    {
        return $this->belongsTo(Department::class, "department_id" , 'id');
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
