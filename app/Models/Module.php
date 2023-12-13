<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "is_enabled",
        "is_default",
        "business_tier_id",
        'created_by'
    ];

    public function business_tier(){
        return $this->belongsTo(businessTier::class,'business_tier_id', 'id');
    }

}
