<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\RiderAssignment;
use Illuminate\Http\Request;

class RiderDashboardController extends Controller
{
    // Rider dashboard stats
    public function stats(Request $request)
    {
        $rider = auth()->user();

        $totalDeliveries = RiderAssignment::where('rider_id', $rider->id)
            ->where('status', 'delivered')->count();

        $activeDeliveries = RiderAssignment::where('rider_id', $rider->id)
            ->whereIn('status', ['assigned', 'picked_up'])->count();

        $todayDeliveries = RiderAssignment::where('rider_id', $rider->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())->count();

        $assignments = RiderAssignment::with(['order.user'])
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['assigned', 'picked_up'])
            ->latest()
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'order_number' => '#' . str_pad($a->order_id, 4, '0', STR_PAD_LEFT),
                    'customer_name' => $a->order->user->name ?? 'Guest',
                    'customer_phone' => $a->order->user->phone ?? 'N/A',
                    'delivery_address' => $a->order->shipping_address ?? [],
                    'status' => $a->status,
                    'assigned_at' => $a->assigned_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_deliveries' => $totalDeliveries,
                    'active_deliveries' => $activeDeliveries,
                    'today_deliveries' => $todayDeliveries,
                ],
                'active_assignments' => $assignments,
            ]
        ]);
    }

    // Update delivery status
    public function updateStatus(Request $request, $assignmentId)
    {
        $request->validate([
            'status' => 'required|in:picked_up,delivered,failed',
            'notes' => 'nullable|string',
        ]);

        $assignment = RiderAssignment::where('id', $assignmentId)
            ->where('rider_id', auth()->id())
            ->firstOrFail();

        $updateData = [
            'status' => $request->status,
            'delivery_notes' => $request->notes,
        ];

        if ($request->status === 'picked_up') {
            $updateData['picked_up_at'] = now();
            $assignment->order->update(['status' => 'shipped']);
        }

        if ($request->status === 'delivered') {
            $updateData['delivered_at'] = now();
            $assignment->order->update(['status' => 'delivered']);
        }

        $assignment->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Delivery status updated to {$request->status}",
        ]);
    }
}
