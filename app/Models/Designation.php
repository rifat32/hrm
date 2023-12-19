<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "created_by"
    ];

    public function disabled(){
        return $this->hasMany(DisabledDesignation::class,'designation_id', 'id');
    }

  
    public function getCreatedAtAttribute($value)
{
    return (new Carbon($value))->format('d/m/Y');
}
public function getUpdatedAtAttribute($value)
{
    return (new Carbon($value))->format('d/m/Y');
}
}
