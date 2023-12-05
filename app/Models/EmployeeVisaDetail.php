<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeVisaDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_passport_details_id',
        'BRP_number',
        "visa_issue_date",
        "visa_expiry_date",
        "place_of_issue",
        "visa_docs",
    ];


    protected $casts = [
        'visa_docs' => 'array',
    ];



}
