<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrunUser extends Model
{
    use HasFactory;
    protected $fillable = [
        'payrun_id', 'user_id'
    ];


    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }



    // public function getCreatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
    // public function getUpdatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
}
