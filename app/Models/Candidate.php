<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'experience_years',
        'education_level',
        "job_platform",
        'cover_letter',
        'application_date',
        'interview_date',
        'feedback',
        'status',
        'job_listing_id',
        'attachments',

        "is_active",
        "business_id",
        "created_by"
    ];

    protected $casts = [
        'attachments' => 'array',

    ];

    public function job_listing()
    {
        return $this->belongsTo(JobListing::class, "job_listing_id",'id');
    }


   




}
