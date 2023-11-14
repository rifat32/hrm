<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentAnnouncement extends Model
{
    use HasFactory;
    protected $fillable = [
        'department_id', 'announcement_id'
    ];
}
