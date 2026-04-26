<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
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
