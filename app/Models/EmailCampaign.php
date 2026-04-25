<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'ab_test_subject',
        'content',
        'segment_id',
        'scheduled_at',
        'status',
        'results',
        'language_id',
        'sent_count',
        'open_count',
        'click_count',
        'revenue',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    protected $casts = [
        'scheduled_at' => 'datetime',
        'results'      => 'array',
        'revenue'      => 'decimal:2',
    ];

    public function segment()
    {
        return $this->belongsTo(UserSegment::class, 'segment_id');
    }

    public function analytics()
    {
        return $this->hasMany(EmailCampaignAnalytics::class);
    }
}
