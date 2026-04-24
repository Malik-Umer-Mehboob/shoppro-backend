<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService
    ) {}

    public function store(Request $request)
    {
        $request->validate([
            'shipping_address'            => 'required|array',
            'shipping_address.full_name'  => 'required|string',
            'shipping_address.phone'      => 'required|string',
            'shipping_address.address_line_1' => 'required|string',
            'shipping_address.city'       => 'required|string',
            'shipping_address.state'      => 'required|string',
            'shipping_address.country'    => 'required|string',
            'shipping_address.postal_code'=> 'required|string',
            'payment_method'              => 'required|in:stripe,paypal,cod',
            'payment_intent_id'           => 'nullable|string',
        ]);

        $user = $request->user();

        // Get user's active cart
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['items.product', 'items.variant'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $paymentData = $request->only(['payment_intent_id', 'paypal_order_id']);

        $order = $this->orderService->createFromCart(
            $cart,
            $request->input('shipping_address'),
            $request->input('payment_method'),
            $paymentData['payment_intent_id'] ?? null
        );

        // Process payment if not COD
        if ($order->payment_method !== 'cod') {
            $result = $this->paymentService->processPayment($order, $paymentData);
            if (!$result['success']) {
                return response()->json(['message' => $result['message']], 422);
            }
        }

        return response()->json([
            'message' => 'Order placed successfully.',
            'order'   => $order->load(['items.product', 'invoice']),
        ], 201);
    }
}
