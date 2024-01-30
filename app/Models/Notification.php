<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        "sender_id",
        "receiver_id",
        "business_id",
        "entity_name",
        "entity_id",


        'notification_title',
        'notification_description',
        'notification_link',
        "is_system_generated",
        "notification_template_id",
        "status",

    ];



    

    public function template(){
        return $this->belongsTo(NotificationTemplate::class,'notification_template_id', 'id');
    }
    public function sender(){
        return $this->belongsTo(User::class,'sender_id', 'id')->withTrashed();
    }
    public function receiver(){
        return $this->belongsTo(User::class,'receiver_id', 'id')->withTrashed();
    }
    public function business(){
        return $this->belongsTo(Business::class,'business_id', 'id')->withTrashed();
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
