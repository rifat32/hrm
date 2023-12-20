<?php

namespace App\Models;

use Carbon\Carbon;
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








    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }






    public function getDateAssignedAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getExpiryDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }

    public function setDateAssignedAttribute($value)
    {
        return (new Carbon($value))->format('Y-m-d');
    }
    public function setExpiryDateAttribute($value)
    {
        return (new Carbon($value))->format('Y-m-d');
    }

}
