<?php
namespace App\Http\Controllers\Api;

use App\Events\OrderRefunded;
use App\Events\OrderShipped;
use App\Events\OrderDelivered;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ActivityLog;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

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

    public function show(Request $request, $id)
    {
        $user  = $request->user();
        $order = Order::with(['customer', 'seller', 'items.product', 'items.variant', 'invoice'])->findOrFail($id);

        if ($user->hasRole('seller') && $order->seller_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($order);
    }

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

        if ($request->filled('status') && $request->status !== $prevStatus) {
            ActivityLog::log('order.status_updated',
                "Order #{$id} status changed to {$request->status}",
                'Order', $id
            );

            if ($request->status === Order::STATUS_SHIPPED)   event(new OrderShipped($order));
            if ($request->status === Order::STATUS_DELIVERED) event(new OrderDelivered($order));
            if ($request->status === Order::STATUS_REFUNDED)  event(new OrderRefunded($order));
        }

        return response()->json(['message' => 'Order updated.', 'order' => $order->fresh()]);
    }

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

    public function cancel(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $cancellationWindow = 24; 
        $hoursSinceOrder = $order->created_at->diffInHours(now());

        if ($hoursSinceOrder > $cancellationWindow) {
            return response()->json([
                'success' => false,
                'message' => "Orders can only be cancelled within {$cancellationWindow} hours of placing. Please contact support.",
            ], 422);
        }

        if (in_array($order->status, ['shipped', 'delivered'])) {
            return response()->json([
                'success' => false,
                'message' => "Cannot cancel order that has already been {$order->status}.",
            ], 422);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already cancelled.',
            ], 422);
        }

        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
        }

        $order->update([
            'status' => 'cancelled',
            'payment_status' => $order->payment_status === 'paid' ? 'refunded' : 'failed',
            'payment_notes' => 'Order cancelled by customer within cancellation window',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully. Stock has been restored.',
            'data' => [
                'order_id' => $order->id,
                'status' => 'cancelled',
                'refund_status' => $order->payment_status === 'paid' 
                    ? 'Refund will be processed within 3-5 business days'
                    : 'No payment was made',
            ]
        ]);
    }

    public function canCancel($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $cancellationWindow = 24;
        $hoursSinceOrder = $order->created_at->diffInHours(now());
        $canCancel = $hoursSinceOrder <= $cancellationWindow
            && !in_array($order->status, ['shipped', 'delivered', 'cancelled']);

        $hoursLeft = max(0, $cancellationWindow - $hoursSinceOrder);

        return response()->json([
            'success' => true,
            'data' => [
                'can_cancel' => $canCancel,
                'hours_left' => round($hoursLeft, 1),
                'order_status' => $order->status,
                'message' => $canCancel
                    ? "You can cancel this order within {$hoursLeft} more hours"
                    : "This order can no longer be cancelled",
            ]
        ]);
    }
}