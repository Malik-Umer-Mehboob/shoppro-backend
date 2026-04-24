<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    protected $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    public function register(Request $request)
    {
        $user = $request->user();

        if ($user->affiliate) {
            return response()->json(['message' => 'Already an affiliate'], 422);
        }

        $request->validate([
            'payout_details' => 'required|array',
        ]);

        $affiliate = $this->affiliateService->registerAffiliate($user->id, [
            'payout_details' => $request->payout_details,
        ]);

        return response()->json($affiliate, 201);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return response()->json(['message' => 'Not an affiliate'], 404);
        }

        $stats = $this->affiliateService->getAffiliateStats($affiliate->id);

        return response()->json([
            'affiliate' => $affiliate,
            'stats' => $stats,
            'referral_url' => $affiliate->referral_url,
        ]);
    }

    public function orders(Request $request)
    {
        $affiliate = $request->user()->affiliate;
        return response()->json($affiliate->orders()->with('order')->latest()->paginate(10));
    }
}
