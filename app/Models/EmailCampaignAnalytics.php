<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaignAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_campaign_id',
        'user_id',
        'opened_at',
        'clicked_at',
        'converted_at',
    ];

    protected $casts = [
        'opened_at'    => 'datetime',
        'clicked_at'   => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
