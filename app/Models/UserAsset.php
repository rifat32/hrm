<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAsset extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'code',
        'serial_number',
        'type',
        'image',
        'date',
        'note',
        'created_by',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by','id');
    }
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }




    public function getDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }



  
}
