<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_blocked',
        'block_reason',
        'blocked_at',
        'google_id',
        'email_preferences',
        'mobile_number',
        'role',
        'subscribed_to_newsletter',
        'unsubscribe_token',
        'referred_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'email_preferences'         => 'array',
            'subscribed_to_newsletter'  => 'boolean',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            if (!$user->unsubscribe_token) {
                $user->update([
                    'unsubscribe_token' => \Illuminate\Support\Str::random(64)
                ]);
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSeller(): bool
    {
        return $this->hasRole('seller');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function getIsSupportAttribute(): bool
    {
        return $this->role === 'support';
    }

    public function getTotalSalesAttribute()
    {
        if ($this->role === 'seller') {
            return $this->sellerOrders()->where('payment_status', 'paid')->sum('grand_total');
        }
        return $this->orders()->where('payment_status', 'paid')->sum('grand_total');
    }

    public function isSupport(): bool
    {
        return $this->hasRole('support') || $this->role === 'support';
    }

    public function isSupportAgent(): bool
    {
        return $this->isSupport();
    }

    public function tickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }

    public function cart(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlist(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wishlist::class);
    }

    public function notifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function sellerOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function riderAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RiderAssignment::class, 'rider_id');
    }

    public function isSubscribedToNewsletter(): bool
    {
        return $this->subscribed_to_newsletter;
    }

    public function getPreferences(): array
    {
        return $this->email_preferences ?? \App\Services\NotificationService::getDefaultPreferences();
    }

    public function setPreferences(array $preferences): void
    {
        $this->update(['email_preferences' => $preferences]);
    }

    public function affiliate(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referralRewards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    public function blogPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    public function blogComments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }
}
