<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPlatform extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        "is_active",
        "is_default",
        "business_id",
    ];
}
