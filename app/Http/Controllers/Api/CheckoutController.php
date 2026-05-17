<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingZone;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'shipping_address'                  => 'required|array',
            'shipping_address.full_name'        => 'required|string',
            'shipping_address.phone'            => 'required|string',
            'shipping_address.address_line_1'   => 'required|string',
            'shipping_address.city'             => 'required|string',
            'shipping_address.country'          => 'required|string',
            'payment_method'                    => 'required|in:cod,bank_transfer,stripe',
            'reference_number'                  => 'required_if:payment_method,bank_transfer|nullable|string',
            'coupon_code'                       => 'nullable|string',
            'notes'                             => 'nullable|string',
        ]);

        // Get cart items
        $cart = Cart::with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty',
            ], 422);
        }

        // Calculate shipping
        $shippingZone = ShippingZone::where('city', $request->shipping_address['city'])
            ->where('is_active', true)
            ->first();

        $shippingCost = $shippingZone?->delivery_charge ?? 350;

        // Calculate subtotal
        $subtotal = $cart->items->sum(function ($item) {
            $price = $item->variant?->price ?? $item->product->sale_price
                ?? $item->product->price;
            return $price * $item->quantity;
        });

        // Apply coupon if provided
        $discount      = 0;
        $couponCode    = null;
        $appliedCoupon = null;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid coupon code',
                ], 422);
            }

            $validation = $coupon->isValidForUser($user->id, $subtotal);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                ], 422);
            }

            $discount      = $coupon->calculateDiscount($subtotal);
            $couponCode    = $coupon->code;
            $appliedCoupon = $coupon;
        }

        // Tax (0 for now)
        $tax        = 0;
        $grandTotal = $subtotal + $shippingCost - $discount + $tax;

        // Create order within a transaction for atomicity
        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $request, $cart, $subtotal, $shippingCost, $discount, $tax, $grandTotal, $couponCode, $appliedCoupon) {
            $order = Order::create([
                'user_id'        => $user->id,
                'status'         => 'pending',
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'subtotal'       => $subtotal,
                'shipping_cost'  => $shippingCost,
                'discount'       => $discount,
                'tax'            => $tax,
                'grand_total'    => $grandTotal,
                'coupon_code'    => $couponCode,
                'notes'          => $request->notes,
            ]);

            // Create order items + deduct stock in bulk if possible, or inside transaction
            foreach ($cart->items as $item) {
                $price = $item->variant?->price ?? $item->product->sale_price
                    ?? $item->product->price;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'seller_id'  => $item->product->seller_id,
                    'name'       => $item->product->name,
                    'quantity'   => $item->quantity,
                    'price'      => $price,
                    'total'      => $price * $item->quantity,
                ]);

                // Ensure stock validation here or via model constraints
                $item->product->decrement('stock_quantity', $item->quantity);
            }

            // Record coupon usage
            if ($appliedCoupon) {
                CouponUsage::create([
                    'coupon_id' => $appliedCoupon->id,
                    'user_id'   => $user->id,
                    'order_id'  => $order->id,
                    'used_at'   => now(),
                ]);
                $appliedCoupon->increment('used_count');
            }

            // Process payment method notes
            $paymentNotes = ($request->payment_method === 'bank_transfer') 
                ? 'Awaiting bank transfer verification. Ref: ' . $request->reference_number 
                : 'Cash to be collected on delivery';

            $order->update([
                'payment_id'    => $request->reference_number,
                'payment_notes' => $paymentNotes,
            ]);

            // Clear cart
            $cart->items()->delete();

            return $order;
        });

        // Dispatch heavy tasks to background queue
        \App\Jobs\ProcessOrderPostCheckout::dispatch($order, $user);
        
        // Load invoice if generated synchronously
        $order->load('invoice');

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully!',
            'data'    => [
                'order_id'           => $order->id,
                'order_number'       => '#' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'invoice_number'     => $order->invoice?->invoice_number,
                'grand_total'        => $grandTotal,
                'payment_method'     => $request->payment_method,
                'payment_status'     => 'pending',
                'estimated_delivery' => $shippingZone
                    ? $shippingZone->estimated_days . ' business days'
                    : '5-7 business days',
                'bank_details' => $request->payment_method === 'bank_transfer' ? [
                    'bank_name'      => 'HBL Bank',
                    'account_title'  => 'ShopPro Pvt Ltd',
                    'account_number' => '1234-5678-9012',
                    'iban'           => 'PK36HABB0000001234567890',
                    'amount'         => 'Rs. ' . number_format($grandTotal, 2),
                    'reference'      => 'Order ' . '#' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                ] : null,
            ],
        ]);
    }
}
