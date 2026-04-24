<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderRefunded;
use App\Events\OrderShipped;
use App\Events\OrderDelivered;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    /**
     * GET /api/admin/orders
     * Admin: all orders. Seller: only their orders.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::with(['customer', 'items', 'invoice'])->latest();

        if ($user->hasRole('seller')) {
            $query->where('seller_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        return response()->json($query->paginate(15));
    }

    /**
     * GET /api/admin/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $user  = $request->user();
        $order = Order::with(['customer', 'seller', 'items.product', 'items.variant', 'invoice'])->findOrFail($id);

        if ($user->hasRole('seller') && $order->seller_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($order);
    }

    /**
     * PUT /api/admin/orders/{id}
     * Update status, tracking_number, notes
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status'          => 'sometimes|in:pending,processing,shipped,delivered,cancelled,returned,refunded',
            'tracking_number' => 'sometimes|nullable|string|max:255',
            'notes'           => 'sometimes|nullable|string',
        ]);

        $user  = $request->user();
        $order = Order::findOrFail($id);

        if ($user->hasRole('seller') && $order->seller_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $prevStatus = $order->status;
        $order->update($request->only(['status', 'tracking_number', 'notes']));

        // Fire status-change events
        if ($request->filled('status') && $request->status !== $prevStatus) {
            if ($request->status === Order::STATUS_SHIPPED)   event(new OrderShipped($order));
            if ($request->status === Order::STATUS_DELIVERED) event(new OrderDelivered($order));
            if ($request->status === Order::STATUS_REFUNDED)  event(new OrderRefunded($order));
        }

        return response()->json(['message' => 'Order updated.', 'order' => $order->fresh()]);
    }

    /**
     * POST /api/admin/orders/{id}/refund
     */
    public function refund(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (!in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED])) {
            return response()->json(['message' => 'Order cannot be refunded in its current state.'], 422);
        }

        $order->update([
            'status'         => Order::STATUS_REFUNDED,
            'payment_status' => Order::PAYMENT_REFUNDED,
        ]);

        $this->orderService->restoreInventory($order);

        event(new OrderRefunded($order));

        return response()->json(['message' => 'Refund processed successfully.', 'order' => $order->fresh()]);
    }
}
