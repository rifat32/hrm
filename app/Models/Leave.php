<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    protected $fillable = [
        'leave_duration',
        'day_type',
        'leave_type_id',
        'employee_id',
        'date',
        'note',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'attachments',
        "status",
        "is_active",
        "business_id",
        "created_by"
    ];

    protected $casts = [
        'attachments' => 'array',
    ];
    public function records(){
        return $this->hasMany(LeaveRecord::class,'leave_id', 'id');
    }
    public function employee() {
        return $this->belongsTo(User::class, "employee_id","id");
    }
}
