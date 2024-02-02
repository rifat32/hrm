<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
       'user_id', 'month', 'year', 'payment_amount', 'payment_date', 'payslip_file', 'payment_record_file', "payroll_id", "created_by"
    ];


    protected $casts = [
        'payment_record_file' => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class,"user_id","id");
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by',"id");
    }

}
