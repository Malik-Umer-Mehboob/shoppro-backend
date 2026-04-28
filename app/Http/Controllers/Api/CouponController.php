<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $coupons
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $coupon = Coupon::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully',
            'data' => $coupon
        ], 201);
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully'
        ]);
    }

    public function validate(Request $request)
    {
        $request->validate([
            'code'         => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $user   = auth()->user();
        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code',
            ], 422);
        }

        $validation = $coupon->isValidForUser($user->id, (float) $request->order_amount);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], 422);
        }

        $discount = $coupon->calculateDiscount((float) $request->order_amount);

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'data'    => [
                'code'            => $coupon->code,
                'type'            => $coupon->type,
                'value'           => $coupon->value,
                'discount_amount' => $discount,
                'message'         => $coupon->type === 'percentage'
                    ? "{$coupon->value}% discount applied"
                    : 'Rs. ' . number_format($discount, 0) . ' discount applied',
            ],
        ]);
    }
}
