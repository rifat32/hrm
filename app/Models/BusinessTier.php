<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessTier extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "is_active",
        'created_by'
    ];

    public function businesses(){
        return $this->hasMany(Business::class,'business_tier_id', 'id');
    }

    public function modules(){
        return $this->hasMany(Module::class,'business_tier_id', 'id');
    }

    public function active_modules(){
        return $this->hasMany(Module::class,'business_tier_id', 'id');
    }
    public function getActiveModuleNamesAttribute()
    {
        return $this->modules->pluck('name')->toArray();
    }



   
}
