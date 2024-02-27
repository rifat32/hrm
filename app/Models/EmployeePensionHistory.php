<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePensionHistory extends Model
{
    use HasFactory;
    protected $fillable = [

        'pension_eligible',
        'pension_enrollment_issue_date',
        'pension_letters',
        'pension_scheme_status',
        'pension_scheme_opt_out_date',
        'pension_re_enrollment_due_date',
        "is_manual",
        'user_id',
        "pension_id",
        "from_date",
        "to_date",
        'created_by'
    ];
    protected $appends = ['is_current'];

    public function getIsCurrentAttribute() {
        $current_user_id = request()->user_id;

        $max_pension_re_enrollment_due_date = static::where('user_id', $current_user_id)
            ->max('pension_re_enrollment_due_date');

            $max_id_with_max_date = static::where('user_id', $current_user_id)
        ->where('pension_re_enrollment_due_date', $max_pension_re_enrollment_due_date)
        ->max('id');

        return $this->user_id == $current_user_id && $this->pension_re_enrollment_due_date == $max_pension_re_enrollment_due_date &&  $this->id == $max_id_with_max_date;

    }


            public function employee_pension(){
                return $this->hasOne(EmployeePension::class,'id', 'pension_id');
            }



    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }



    protected $casts = [
        'pension_letters' => 'array',
    ];
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by',"id");
    }


}
