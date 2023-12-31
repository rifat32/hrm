<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAddressHistory extends Model
{
    use HasFactory;
    protected $fillable = [

        "address_line_1",
        "address_line_2",
        "country",
        "city",
        "postcode",
        "lat",
        "long",




        'employee_id',
        "from_date",
        "to_date",
        'created_by'
    ];



    public function employee(){
        return $this->hasOne(User::class,'id', 'employee_id');
    }





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
