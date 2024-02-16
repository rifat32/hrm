<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSponsorship extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'business_id',
        'date_assigned',
        'expiry_date',
        'status',
        'note',
        "certificate_number",
        "current_certificate_status",
        "is_sponsorship_withdrawn",
        'created_by'
    ];

    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }






}
