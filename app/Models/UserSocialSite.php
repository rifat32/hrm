<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSocialSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_site_id',
        'user_id',
        'profile_link',
        "created_by"
    ];

    public function social_link()
    {
        return $this->belongsTo(SocialSite::class, 'social_site_id', 'id');
    }
}
