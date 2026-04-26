<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RiderAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class RiderController extends Controller
{
    // Get all riders
    public function getRiders()
    {
        $riders = User::whereHas('roles', function ($q) {
                $q->where('name', 'rider');
            })
            ->withCount(['riderAssignments as active_deliveries' => function ($q) {
                $q->whereIn('status', ['assigned', 'picked_up']);
            }])
            ->get(['id', 'name', 'email', 'avatar']);

        return response()->json([
            'success' => true,
            'data' => $riders,
        ]);
    }

    // Assign order to rider
    public function assignRider(Request $request, $orderId)
    {
        $request->validate([
            'rider_id' => 'required|exists:users,id',
        ]);

        $order = Order::findOrFail($orderId);
        $rider = User::findOrFail($request->rider_id);

        if (!$rider->hasRole('rider')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a rider',
            ], 422);
        }

        // Check if already assigned
        $existing = RiderAssignment::where('order_id', $orderId)
            ->whereIn('status', ['assigned', 'picked_up'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Order already assigned to a rider',
            ], 422);
        }

        $assignment = RiderAssignment::create([
            'order_id' => $orderId,
            'rider_id' => $request->rider_id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        // Update order status to processing
        $order->update(['status' => 'processing']);

        return response()->json([
            'success' => true,
            'message' => "Order assigned to {$rider->name}",
            'data' => $assignment,
        ]);
    }

    // Get all assignments
    public function assignments(Request $request)
    {
        $query = RiderAssignment::with(['order.user', 'rider']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by rider
        if ($request->rider_id) {
            $query->where('rider_id', $request->rider_id);
        }

        $assignments = $query->latest()->paginate(15);

        $mapped = $assignments->through(function ($assignment) {
            return [
                'id' => $assignment->id,
                'order_number' => '#' . str_pad($assignment->order_id, 4, '0', STR_PAD_LEFT),
                'order_id' => $assignment->order_id,
                'customer_name' => $assignment->order->user->name ?? 'Guest',
                'customer_email' => $assignment->order->user->email ?? '',
                'rider_name' => $assignment->rider->name ?? 'Unknown',
                'rider_email' => $assignment->rider->email ?? '',
                'status' => $assignment->status,
                'assigned_at' => $assignment->assigned_at
                    ? \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, Y H:i')
                    : null,
                'picked_up_at' => $assignment->picked_up_at
                    ? \Carbon\Carbon::parse($assignment->picked_up_at)->format('M d, Y H:i')
                    : null,
                'delivered_at' => $assignment->delivered_at
                    ? \Carbon\Carbon::parse($assignment->delivered_at)->format('M d, Y H:i')
                    : null,
                'delivery_notes' => $assignment->delivery_notes,
            ];
        });

        // Stats
        $totalAssigned = RiderAssignment::where('status', 'assigned')->count();
        $totalPickedUp = RiderAssignment::where('status', 'picked_up')->count();
        $totalDelivered = RiderAssignment::where('status', 'delivered')->count();
        $totalFailed = RiderAssignment::where('status', 'failed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'assignments' => $mapped,
                'stats' => [
                    'assigned' => $totalAssigned,
                    'picked_up' => $totalPickedUp,
                    'delivered' => $totalDelivered,
                    'failed' => $totalFailed,
                ],
            ],
        ]);
    }
}
