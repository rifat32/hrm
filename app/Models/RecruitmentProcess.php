<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentProcess extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "use_in_employee",
        "use_in_on_boarding",

        "employee_order_no",
        "candidate_order_no",
        "created_by",
        "parent_id",
    ];
    public function getEmployeeOrderNoAttribute($value)
    {
        $recruitment_process_order = RecruitmentProcessOrder::where([
            "recruitment_process_id" => $this->id,
            "business_id" => auth()->user()->business_id
        ])->first();

        if (!empty($recruitment_process_order)) {
            return $recruitment_process_order->employee_order_no;
        }

        return $value;
    }

    public function getCandidateOrderNoAttribute($value)
    {
        $recruitment_process_order = RecruitmentProcessOrder::where([
            "recruitment_process_id" => $this->id,
            "business_id" => auth()->user()->business_id
        ])->first();

        if (!empty($recruitment_process_order)) {
            return $recruitment_process_order->candidate_order_no;
        }

        return $value;
    }

    public function disabled()
    {
        return $this->hasMany(DisabledRecruitmentProcess::class, 'recruitment_process_id', 'id');
    }


  

    // public function users() {
    //     return $this->belongsToMany(User::class, 'user_recruitment_processes', 'recruitment_process_id', 'user_id');
    // }
    // public function user_recruitment_processes() {
    //     return $this->hasOne(UserRecruitmentProcess::class, 'recruitment_process_id', 'id');
    // }


    // public function getCreatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
    // public function getUpdatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }

    public function orders()
    {
        return $this->hasMany(RecruitmentProcessOrder::class, 'recruitment_process_id');
    }



}
