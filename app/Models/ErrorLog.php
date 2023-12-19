<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';
    protected $fillable = [
        "api_url",
        "user",
        "user_id",
        "message",
        "status_code",
        "line",
        "file",
        "ip_address",
        "request_method"


    ];
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d/m/Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d/m/Y');
    }
}
