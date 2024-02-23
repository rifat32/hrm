<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'pension_eligible',
        'pension_enrolment_issue_date',
        'pension_letters',
        'pension_scheme_status',
        'pension_scheme_opt_out_date',
        'pension_re_enrollment_due_date',
        'created_by'
    ];

    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }
    protected $casts = [
        'pension_letters' => 'array',
    ];



}
