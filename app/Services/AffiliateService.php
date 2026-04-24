<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateOrder;
use App\Models\Order;
use Illuminate\Support\Str;

class AffiliateService
{
    public function registerAffiliate($userId, $data = [])
    {
        return Affiliate::create([
            'user_id' => $userId,
            'code' => strtoupper(Str::random(8)),
            'commission_rate' => $data['commission_rate'] ?? 10.00,
            'payout_threshold' => $data['payout_threshold'] ?? 50.00,
            'status' => 'pending',
            'payout_details' => $data['payout_details'] ?? null,
        ]);
    }

    public function getAffiliateByCode($code)
    {
        return Affiliate::where('code', $code)->where('status', 'active')->first();
    }

    public function trackClick($affiliateId, $requestData)
    {
        return AffiliateClick::create([
            'affiliate_id' => $affiliateId,
            'ip_address' => $requestData['ip'] ?? null,
            'user_agent' => $requestData['user_agent'] ?? null,
            'referrer_url' => $requestData['referrer'] ?? null,
            'landing_url' => $requestData['landing'] ?? null,
        ]);
    }

    public function calculateCommission(Order $order, Affiliate $affiliate)
    {
        $amount = ($order->subtotal - $order->discount) * ($affiliate->commission_rate / 100);
        
        return AffiliateOrder::create([
            'order_id' => $order->id,
            'affiliate_id' => $affiliate->id,
            'commission_amount' => round($amount, 2),
            'status' => 'pending',
        ]);
    }

    public function getAffiliateStats($affiliateId)
    {
        $affiliate = Affiliate::findOrFail($affiliateId);
        
        return [
            'clicks' => $affiliate->clicks()->count(),
            'orders' => $affiliate->orders()->count(),
            'total_commission' => $affiliate->orders()->sum('commission_amount'),
            'paid_commission' => $affiliate->orders()->where('status', 'paid')->sum('commission_amount'),
            'pending_commission' => $affiliate->orders()->where('status', 'pending')->sum('commission_amount'),
        ];
    }
}
