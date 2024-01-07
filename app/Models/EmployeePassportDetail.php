<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }






    public function getPassportIssueDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getPassportExpiryDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }




}
