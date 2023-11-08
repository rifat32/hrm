<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        "sender_id",
        "receiver_id",
        "customer_id",
        "business_id",



      
        "notification_template_id",
        "status",

    ];




    public function template(){
        return $this->belongsTo(NotificationTemplate::class,'notification_template_id', 'id');
    }
    public function customer(){
        return $this->belongsTo(User::class,'customer_id', 'id')->withTrashed();
    }
    public function business(){
        return $this->belongsTo(Business::class,'business_id', 'id')->withTrashed();
    }



}
