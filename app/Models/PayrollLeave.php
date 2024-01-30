<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'leave_id',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function leave()
    {
        return $this->belongsTo(Leave::class, 'leave_id');
    }





    



}
