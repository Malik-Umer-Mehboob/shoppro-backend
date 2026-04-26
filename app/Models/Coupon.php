<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'minimum_order_amount',
        'max_uses',
        'per_user_limit',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
        'value'      => 'float',
        'minimum_order_amount' => 'float',
        'max_uses'   => 'integer',
        'per_user_limit' => 'integer',
        'used_count' => 'integer',
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function userUsages($userId)
    {
        return $this->usages()->where('user_id', $userId);
    }

    public function isValidForUser(int $userId, float $orderAmount): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This coupon is no longer active'];
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return ['valid' => false, 'message' => 'This coupon has expired'];
        }

        if ($this->minimum_order_amount && $orderAmount < $this->minimum_order_amount) {
            return [
                'valid'   => false,
                'message' => 'Minimum order amount of Rs. '
                    . number_format($this->minimum_order_amount, 0)
                    . ' required for this coupon',
            ];
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return [
                'valid'   => false,
                'message' => 'This coupon has reached its maximum usage limit',
            ];
        }

        $userUsageCount = $this->userUsages($userId)->count();
        if ($userUsageCount >= $this->per_user_limit) {
            return [
                'valid'   => false,
                'message' => $this->per_user_limit === 1
                    ? 'You have already used this coupon'
                    : "You can only use this coupon {$this->per_user_limit} times",
            ];
        }

        return ['valid' => true, 'message' => 'Coupon is valid'];
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            return round(($subtotal * $this->value / 100), 2);
        }
        return min($this->value, $subtotal);
    }
}
