<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePlan extends Model
{
    use HasFactory;

    protected $fillable = [
      "name",
      "description",
        'set_up_amount',
        'number_of_employees_allowed',
        'duration_months',
        'price',
        'business_tier_id',
        "created_by"
    ];



    public function active_modules()
    {
        return $this->hasMany(ServicePlanModule::class, 'service_plan_id', 'id');
    }





    public function business_tier(){
        return $this->belongsTo(BusinessTier::class,'business_tier_id', 'id');
    }






    public function discount_codes()
    {
        return $this->hasMany(ServicePlanDiscountCode::class,"service_plan_id","id");
    }
























































































}
