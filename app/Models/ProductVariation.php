<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        "sub_sku",
        "quantity",
        "price",
        "automobile_make_id",
        "product_id",


    ];

    public function product(){
        return $this->belongsTo(Product::class,'product_id', 'id');
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
