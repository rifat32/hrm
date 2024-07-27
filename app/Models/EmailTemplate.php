<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [

        "name",
        "type",
        "template",
        "is_active",
        "is_default",
        "business_id",
        'wrapper_id',

    ];

    // public function getTemplateAttribute($value)
    // {
    //     return json_decode($value);
    // }


}
