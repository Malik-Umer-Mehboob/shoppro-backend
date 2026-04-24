<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateOrder;
use Illuminate\Http\Request;

class AffiliateManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Affiliate::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function updateStatus(Request $request, Affiliate $affiliate)
    {
        $request->validate([
            'status' => 'required|in:active,rejected,inactive',
            'commission_rate' => 'numeric|min:0|max:100',
        ]);

        $affiliate->update($request->only(['status', 'commission_rate']));

        return response()->json($affiliate);
    }

    public function orders()
    {
        return response()->json(AffiliateOrder::with('affiliate.user', 'order')->latest()->paginate(20));
    }

    public function updateOrder(Request $request, AffiliateOrder $affiliateOrder)
    {
        $request->validate([
            'status' => 'required|in:paid,cancelled',
        ]);

        $affiliateOrder->update(['status' => $request->status]);

        return response()->json($affiliateOrder);
    }
}
