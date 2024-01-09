<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProjectHistory extends Model
{
    use HasFactory;

    protected $fillable = [



        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        "is_active",
        "business_id",
        "created_by",
        "project_id",
        "from_date",
        "to_date",
        "employee_id",


    ];

    // public function departments() {
    //     return $this->belongsToMany(Department::class, 'department_projects', 'project_id', 'department_id');
    // }





    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }









    public function getFromDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getToDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }





}
