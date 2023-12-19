<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        "name",
        "type",
        "sku",
        "description",
        "image",
        // "is_active",
        "is_default",
        "shop_id",
        "product_category_id"

    ];
    public function product_variations(){
        return $this->hasMany(ProductVariation::class,'product_id', 'id');
    }
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }

}
