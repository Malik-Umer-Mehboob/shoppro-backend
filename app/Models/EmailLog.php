<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'recipient_email',
        'template_name',
        'status',
        'error_message',
        'campaign_id',
        'user_id'
    ];
}
