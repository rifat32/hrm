<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessPensionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        "pension_scheme_registered",
        "pension_scheme_name",
        "pension_scheme_letters",
        "business_id",
        "created_by"
    ];









}
