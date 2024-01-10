<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'db_table_name',
        'db_field_name',
        'duration',
        'duration_unit',
        'send_time',
        'frequency_after_first_reminder',
        'keep_sending_until_update',
        'entity_name',
        "business_id"
    ];

    protected $casts = [
        'keep_sending_until_update' => 'boolean',
    ];
    protected $hidden = [
        'db_table_name',
        'db_field_name',
    ];
}
