<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class businessTier extends Model
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



}
