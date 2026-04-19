<?php

namespace App\Models;

class SmsMessage extends BaseModel
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
