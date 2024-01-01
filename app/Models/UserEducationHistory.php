<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEducationHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'degree',
        'major',
        'school_name',
        'graduation_date',
        'start_date',
        'gpa',
        'achievements',
        'description',
        'address',
        'country',
        'city',
        'postcode',
        'is_current',
        'created_by',
    ];



    public function user()
    {
        return $this->belongsTo(User::class,"user_id","id");
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by',"id");
    }

    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }


    public function getGraduationDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }

    public function getStartDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }


}
