<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeVisaDetailHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'BRP_number',
        "visa_issue_date",
        "visa_expiry_date",
        "place_of_issue",
        "visa_docs",



        "is_manual",
        'user_id',
        "from_date",
        "to_date",
        "visa_detail_id",
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by',"id");
    }


    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }

    protected $casts = [
        'visa_docs' => 'array',

    ];




    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }




    public function getVisaIssueDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getVisaExpiryDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }

    public function getFromDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getToDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
}
