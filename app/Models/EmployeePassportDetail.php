<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePassportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        "employee_id",
        'passport_number',
        "passport_issue_date",
        "passport_expiry_date",
        "place_of_issue",
        'created_by'
    ];




}
