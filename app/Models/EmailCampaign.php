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
    ];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    protected $casts = [
        'scheduled_at' => 'datetime',
        'results'      => 'array',
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
