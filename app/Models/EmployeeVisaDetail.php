<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeVisaDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'BRP_number',
        "visa_issue_date",
        "visa_expiry_date",
        "place_of_issue",
        "visa_docs",
        'created_by'
    ];

    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }


    protected $casts = [
        'visa_docs' => 'array',

    ];







}
