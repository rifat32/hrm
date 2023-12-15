<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSponsorship extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'date_assigned',
        'expiry_date',
        'status',
        'note',
        "certificate_number",
        "current_certificate_status",
        "is_sponsorship_withdrawn",
        'created_by'
    ];
  


}
