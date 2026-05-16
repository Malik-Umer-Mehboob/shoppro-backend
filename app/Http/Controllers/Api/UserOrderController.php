<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class UserOrderController extends Controller
{
    /**
     * GET /api/user/orders
     */
    public function index(Request $request)
    {
        $user   = $request->user();
        $query  = Order::where('user_id', $user->id)->with(['items.product', 'invoice'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(10));
    }

    /**
     * GET /api/user/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $user  = $request->user();
        $order = Order::where('user_id', $user->id)
            ->with(['items.product', 'items.variant', 'invoice'])
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * POST /api/user/orders/{id}/refund-request
     * Customer requests a refund.
     */
    public function requestRefund(Request $request, $id)
    {
        $user  = $request->user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if (!$order->canBeRefunded()) {
            return response()->json([
                'message' => 'Refund is only allowed within 30 days of delivery, and only for delivered orders.',
            ], 422);
        }

        $order->update(['status' => Order::STATUS_RETURNED, 'notes' => 'Refund requested by customer.']);

        return response()->json(['message' => 'Refund request submitted. Admin will process it shortly.', 'order' => $order->fresh()]);
    }

    /**
     * GET /api/user/orders/eligible-returns
     */
    public function eligibleReturns(Request $request)
    {
        $user = $request->user();
        
        $orders = Order::where('user_id', $user->id)
            ->where(function($q) {
                $q->whereRaw('LOWER(status) = ?', ['delivered'])
                  ->orWhere('status', Order::STATUS_DELIVERED);
            })
            ->where(function($q) {
                $q->where(function($sq) {
                    $sq->whereNotNull('delivered_at')
                       ->where('delivered_at', '>=', now()->subDays(30));
                })->orWhere(function($sq) {
                    $sq->whereNull('delivered_at')
                       ->where('updated_at', '>=', now()->subDays(30));
                });
            })
            ->with(['items.product'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
}
