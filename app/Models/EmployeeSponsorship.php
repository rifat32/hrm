<?php

namespace App\Models;

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
        'created_by'
    ];
    public function passport()
    {
        return $this->hasOne(EmployeePassportDetail::class, 'employee_sponsorship_id', 'id');
    }


}
