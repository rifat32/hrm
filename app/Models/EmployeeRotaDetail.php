<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRotaDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_rota_id',
        'day',
        "start_at",
        'end_at',
        'is_weekend',
    ];

}
