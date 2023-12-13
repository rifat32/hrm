<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory,  SoftDeletes;
    protected $fillable = [
        "name",
        "about",
        "web_page",
        "phone",
        "email",
        "additional_information",
        "address_line_1",
        "address_line_2",
        "lat",
        "long",
        "country",
        "city",
        "currency",
        "postcode",
        "logo",
        "image",
        "background_image",
        "status",
         "is_active",



        "business_tier_id",
        "owner_id",
        'created_by'

    ];

    public function owner(){
        return $this->belongsTo(User::class,'owner_id', 'id');
    }


    public function business_tier(){
        return $this->belongsTo(businessTier::class,'business_tier_id', 'id');
    }
































































}
