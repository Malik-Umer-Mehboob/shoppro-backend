<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    public function generateReferralCode(User $user)
    {
        return strtoupper(substr($user->name, 0, 3)) . $user->id . Str::random(4);
    }

    public function createReferral($referrerId, $refereeEmail = null)
    {
        return Referral::create([
            'referrer_id' => $referrerId,
            'referee_email' => $refereeEmail,
            'referral_code' => Str::random(10),
            'status' => 'pending',
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function linkReferee(Referral $referral, User $referee)
    {
        $referral->update([
            'referee_id' => $referee->id,
            'status' => 'pending', // Still pending until first purchase
        ]);

        $referee->update(['referred_by' => $referral->referrer_id]);

        // Reward referee immediately (discount on first order)
        $this->createReward($referral, $referee->id, 'referee', 10.00); // $10 discount
    }

    public function completeReferral(Referral $referral)
    {
        $referral->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Reward referrer
        $this->createReward($referral, $referral->referrer_id, 'referrer', 20.00); // $20 store credit
    }

    protected function createReward(Referral $referral, $userId, $type, $amount)
    {
        return ReferralReward::create([
            'referral_id' => $referral->id,
            'user_id' => $userId,
            'type' => $type,
            'reward_amount' => $amount,
            'reward_type' => $type === 'referee' ? 'discount_code' : 'store_credit',
            'reward_code' => $type === 'referee' ? 'REF-' . strtoupper(Str::random(6)) : null,
            'is_used' => false,
        ]);
    }
}
