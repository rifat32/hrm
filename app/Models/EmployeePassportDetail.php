<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePassportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "business_id",
        'passport_number',
        "passport_issue_date",
        "passport_expiry_date",
        "place_of_issue",
        'created_by'
    ];

    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }

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
