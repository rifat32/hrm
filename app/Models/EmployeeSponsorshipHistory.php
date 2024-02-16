<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSponsorshipHistory extends Model
{
    use HasFactory;
    protected $fillable = [

        'date_assigned',
        'expiry_date',
        // 'status',
        'note',
        "certificate_number",
        "current_certificate_status",
        "is_sponsorship_withdrawn",

        "is_manual",
        'user_id',
        "sponsorship_id",
        "from_date",
        "to_date",
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by',"id");
    }


    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }














}
