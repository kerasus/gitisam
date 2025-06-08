<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $fillable = [
        'unit_id',
        'mobile',
        'message',
        'template_id',
        'status',
        'message_id',
        'sent_at',
    ];

    // Relationship with Unit model
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
