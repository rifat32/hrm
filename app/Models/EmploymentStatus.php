<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'color',
        "is_active",
        "is_default",
        "business_id",
    ];
}
