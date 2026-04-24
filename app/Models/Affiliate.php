<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'commission_rate',
        'payout_threshold',
        'status',
        'payout_details',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'commission_rate' => 'decimal:2',
        'payout_threshold' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(AffiliateOrder::class);
    }

    public function getReferralUrlAttribute(): string
    {
        return config('app.frontend_url') . '/?ref=' . $this->code;
    }
}
