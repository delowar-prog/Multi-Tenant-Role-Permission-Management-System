<?php

namespace App\Models;


class SupportMessage extends TenantModel
{
    protected $fillable = ['ticket_id', 'sender_id', 'sender_type', 'message', 'attachments'];
    
    protected $casts = [
        'attachments' => 'array',
    ];
}
