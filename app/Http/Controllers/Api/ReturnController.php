<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    // Customer: create return request
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_item_id' => 'nullable|exists:order_items,id',
            'reason' => 'required|in:defective,wrong_item,not_as_described,changed_mind,damaged_in_shipping,other',
            'description' => 'nullable|string|max:1000',
        ]);

        // Verify order belongs to user
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Can only return delivered orders
        if ($order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'You can only return delivered orders',
            ], 422);
        }

        // Check 14-day return window
        $daysSinceDelivery = $order->updated_at->diffInDays(now());
        if ($daysSinceDelivery > 14) {
            return response()->json([
                'success' => false,
                'message' => 'Return window has expired. Returns must be requested within 14 days of delivery.',
            ], 422);
        }

        // Check if already has pending return
        $existing = ReturnRequest::where('order_id', $request->order_id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A return request already exists for this order',
            ], 422);
        }

        $returnRequest = ReturnRequest::create([
            'order_id' => $request->order_id,
            'user_id' => $user->id,
            'order_item_id' => $request->order_item_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        \App\Helpers\NotificationHelper::sendToRole(
            'admin',
            'return.new',
            'New Return Request ↩️',
            "Customer {$user->name} requested return for order #"
                . str_pad($request->order_id, 4, '0', STR_PAD_LEFT),
            ['url' => '/admin/orders']
        );

        return response()->json([
            'success' => true,
            'message' => 'Return request submitted successfully',
            'data' => $returnRequest,
        ]);
    }

    // Customer: get own return requests
    public function index(Request $request)
    {
        $returns = ReturnRequest::with(['order'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'order_number' => '#' . str_pad($r->order_id, 4, '0', STR_PAD_LEFT),
                    'reason' => ucfirst(str_replace('_', ' ', $r->reason)),
                    'description' => $r->description,
                    'status' => $r->status,
                    'refund_amount' => $r->refund_amount,
                    'refund_type' => $r->refund_type,
                    'admin_notes' => $r->admin_notes,
                    'created_at' => $r->created_at->format('M d, Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $returns,
        ]);
    }

    // Admin: get all return requests
    public function adminIndex(Request $request)
    {
        $query = ReturnRequest::with(['order.user', 'orderItem.product']);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $returns = $query->latest()->paginate(15);

        $mapped = $returns->through(function ($r) {
            return [
                'id' => $r->id,
                'order_number' => '#' . str_pad($r->order_id, 4, '0', STR_PAD_LEFT),
                'customer_name' => $r->order?->user?->name ?? 'Unknown',
                'customer_email' => $r->order?->user?->email ?? '',
                'product_name' => $r->orderItem->product->name ?? 'Full Order',
                'reason' => ucfirst(str_replace('_', ' ', $r->reason)),
                'description' => $r->description,
                'status' => $r->status,
                'refund_amount' => $r->refund_amount,
                'refund_type' => $r->refund_type,
                'admin_notes' => $r->admin_notes,
                'order_total' => $r->order->grand_total,
                'created_at' => $r->created_at->format('M d, Y'),
            ];
        });

        $stats = [
            'pending' => ReturnRequest::where('status', 'pending')->count(),
            'approved' => ReturnRequest::where('status', 'approved')->count(),
            'refunded' => ReturnRequest::where('status', 'refunded')->count(),
            'rejected' => ReturnRequest::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => ['returns' => $mapped, 'stats' => $stats],
        ]);
    }

    // Admin: approve return
    public function approve(Request $request, $id)
    {
        $request->validate([
            'refund_type' => 'required|in:full_refund,partial_refund,store_credit,replacement',
            'refund_amount' => 'required|numeric|min:0',
            'admin_notes' => 'nullable|string',
        ]);

        $return = ReturnRequest::findOrFail($id);
        $return->update([
            'status' => 'approved',
            'refund_type' => $request->refund_type,
            'refund_amount' => $request->refund_amount,
            'admin_notes' => $request->admin_notes,
            'approved_at' => now(),
        ]);

        \App\Helpers\NotificationHelper::send(
            $return->user_id,
            'return.approved',
            'Return Request Approved! ✅',
            'Your return request for order #'
                . str_pad($return->order_id, 4, '0', STR_PAD_LEFT)
                . ' has been approved.',
            ['url' => '/returns']
        );

        return response()->json([
            'success' => true,
            'message' => 'Return request approved',
        ]);
    }

    // Admin: reject return
    public function reject(Request $request, $id)
    {
        $return = ReturnRequest::findOrFail($id);
        $return->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes ?? 'Request rejected by admin',
        ]);

        \App\Helpers\NotificationHelper::send(
            $return->user_id,
            'return.rejected',
            'Return Request Update',
            'Your return request for order #'
                . str_pad($return->order_id, 4, '0', STR_PAD_LEFT)
                . ' was not approved.',
            ['url' => '/returns']
        );

        return response()->json([
            'success' => true,
            'message' => 'Return request rejected',
        ]);
    }

    // Admin: mark as refunded
    public function markRefunded($id)
    {
        $return = ReturnRequest::findOrFail($id);
        $return->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        // Update order payment status
        if ($return->order) {
            $return->order->update(['payment_status' => 'refunded']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marked as refunded',
        ]);
    }
}
