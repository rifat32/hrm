<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'set_up_amount',
        'monthly_amount',
        'business_tier_id',
        "created_by"
    ];

    public function business_tier()
    {
        return $this->belongsTo(BusinessTier::class);
    }


}
