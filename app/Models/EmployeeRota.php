<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRota extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'type',
        "description",
        'attendances_count',
        'is_business_default',
        'is_personal',
    
        "is_default",
        "is_active",
        "business_id",
        "created_by"
    ];


    protected $dates = [
    'start_date',
    'end_date'
];



    public function details(){
        return $this->hasMany(EmployeeRotaDetail::class,'employee_rota_id', 'id');
    }



    public function departments() {
        return $this->belongsToMany(Department::class, 'department_employee_rotas', 'employee_rota_id', 'department_id');
    }



    public function users() {
        return $this->belongsToMany(User::class, 'user_employee_rotas', 'employee_rota_id', 'user_id');
    }




}
