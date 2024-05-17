<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'experience_years',
        'education_level',

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

    public function job_platforms() {
        return $this->belongsToMany(JobPlatform::class, 'candidate_job_platforms', 'candidate_id', 'job_platform_id');
    }

// Define your model properties and relationships here

protected static function boot()
{
    parent::boot();

    // Listen for the "deleting" event on the Candidate model
    static::deleting(function($candidate) {
        // Call the deleteFiles method to delete associated files
        $candidate->deleteFiles();
    });
}

/**
 * Delete associated files.
 *
 * @return void
 */



public function deleteFiles()
{
    // Get the file paths associated with the candidate
    $filePaths = $this->attachments;

    // Iterate over each file and delete it
    foreach ($filePaths as $filePath) {
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }
}



}
