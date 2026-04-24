<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $referrals = $user->referrals()->with('referee', 'rewards')->latest()->get();
        $rewards = $user->referralRewards()->latest()->get();

        return response()->json([
            'referrals' => $referrals,
            'rewards' => $rewards,
            'stats' => [
                'total_referrals' => $referrals->count(),
                'completed_referrals' => $referrals->where('status', 'completed')->count(),
                'total_earned' => $rewards->sum('reward_amount'),
            ],
            'referral_code' => $this->referralService->generateReferralCode($user),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $referral = $this->referralService->createReferral($request->user()->id, $request->email);

        // Here you would typically send an email invitation
        
        return response()->json($referral, 201);
    }
}
