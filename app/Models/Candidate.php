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

    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }





    public function getApplicationDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getInterviewDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }




}
