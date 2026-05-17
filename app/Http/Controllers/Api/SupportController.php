<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * Look up an order by ID and customer email.
     */
    public function orderLookup(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'email'        => 'required|email',
        ]);

        // Clean order number (e.g., "#0005" -> "5")
        $cleanOrderNumber = ltrim(str_replace('#', '', $request->order_number), '0');

        // Find the order by ID and verify it belongs to the user
        $order = Order::with(['items.product', 'items.variant'])
            ->where('id', $cleanOrderNumber)
            ->where(function ($query) use ($request) {
                // Secure lookup: match authenticated user OR match user email
                $user = auth('sanctum')->user();
                if ($user) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->whereHas('user', function ($q) use ($request) {
                        $q->where('email', trim($request->email));
                    });
                }
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or email does not match our records.'
            ], 404);
        }

        // Return the order data formatted for the frontend
        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $order->id,
                'status'           => $order->status,
                'payment_status'   => $order->payment_status,
                'tracking_number'  => $order->tracking_number,
                'estimated_delivery' => $order->created_at->addDays(5)->format('Y-m-d'), // Simulated
                'grand_total'      => $order->grand_total,
                'created_at'       => $order->created_at,
                'items'            => $order->items,
                'shipping_address' => $order->shipping_address,
                'shipment_updates' => [ // Simulated updates
                    ['date' => $order->created_at->format('Y-m-d H:i'), 'status' => 'Order Placed', 'message' => 'Your order has been received.'],
                    ['date' => $order->created_at->addHours(2)->format('Y-m-d H:i'), 'status' => 'Payment Confirmed', 'message' => 'Payment was successfully processed.'],
                ]
            ]
        ]);
    }
}
