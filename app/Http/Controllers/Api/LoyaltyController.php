<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function getStatus()
    {
        $user = auth()->user()->load('loyaltyTier');
        $tiers = LoyaltyTier::orderBy('threshold', 'asc')->get();
        
        $nextTier = LoyaltyTier::where('threshold', '>', $user->total_loyalty_points)
            ->orderBy('threshold', 'asc')
            ->first();

        return response()->json([
            'points' => $user->total_loyalty_points,
            'current_tier' => $user->loyaltyTier,
            'next_tier' => $nextTier,
            'history' => LoyaltyPoint::where('user_id', $user->id)->latest()->take(10)->get()
        ]);
    }

    public function getRewards()
    {
        return response()->json(LoyaltyReward::where('is_active', true)->get());
    }

    public function redeemReward(Request $request)
    {
        $request->validate(['reward_id' => 'required|exists:loyalty_rewards,id']);
        
        $user = auth()->user();
        $reward = LoyaltyReward::findOrFail($request->reward_id);

        if ($user->total_loyalty_points < $reward->points_required) {
            return response()->json(['message' => 'Insufficient points.'], 422);
        }

        // Deduct points
        $user->total_loyalty_points -= $reward->points_required;
        $user->save();

        LoyaltyPoint::create([
            'user_id' => $user->id,
            'points' => -$reward->points_required,
            'type' => 'redeem',
            'description' => "Redeemed reward: {$reward->name}"
        ]);

        return response()->json(['message' => 'Reward redeemed successfully!', 'new_points' => $user->total_loyalty_points]);
    }

    // Helper to award points (could be called from OrderService)
    public static function awardPoints($userId, $amount, $description)
    {
        $user = User::find($userId);
        $points = floor($amount); // 1 point per $1

        $user->total_loyalty_points += $points;
        $user->save();

        LoyaltyPoint::create([
            'user_id' => $user->id,
            'points' => $points,
            'type' => 'earn',
            'description' => $description
        ]);

        // Check for tier upgrade
        $newTier = LoyaltyTier::where('threshold', '<=', $user->total_loyalty_points)
            ->orderBy('threshold', 'desc')
            ->first();

        if ($newTier && $user->loyalty_tier_id != $newTier->id) {
            $user->loyalty_tier_id = $newTier->id;
            $user->save();
        }
    }
}
