<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\RiderAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderDashboardController extends Controller
{
    /**
     * Rider dashboard stats
     */
    public function stats(Request $request)
    {
        $rider = $request->user();

        $totalDeliveries = RiderAssignment::where('rider_id', $rider->id)
            ->where('status', 'delivered')->count();

        $activeDeliveries = RiderAssignment::where('rider_id', $rider->id)
            ->whereIn('status', ['pending', 'picked_up', 'assigned'])->count();

        $todayDeliveries = RiderAssignment::where('rider_id', $rider->id)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())->count();

        $assignments = RiderAssignment::with(['order.user'])
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['pending', 'picked_up', 'assigned'])
            ->latest()
            ->get()
            ->map(function ($a) {
                // Parse shipping_address — may be a JSON string or already an array
                $shippingAddr = null;
                if ($a->order?->shipping_address) {
                    $raw = $a->order->shipping_address;
                    $shippingAddr = is_string($raw)
                        ? json_decode($raw, true)
                        : (array) $raw;
                }

                // Prefer phone from shipping address (entered at checkout), fall back to user profile
                $phone = $shippingAddr['phone']
                    ?? $a->order?->user?->mobile_number
                    ?? null;

                // Prefer full_name from shipping address, fall back to user account name
                $name = $shippingAddr['full_name']
                    ?? $a->order?->user?->name
                    ?? 'Guest';

                return [
                    'id'               => $a->id,
                    'order_number'     => '#' . str_pad($a->order_id, 4, '0', STR_PAD_LEFT),
                    'customer_name'    => $name,
                    'customer_phone'   => $phone,
                    'delivery_address' => $shippingAddr,
                    'status'           => $a->status,
                    'assigned_at'      => $a->created_at->diffForHumans(),
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

    /**
     * Get all assignments for the rider.
     */
    public function assignments(Request $request)
    {
        $rider = $request->user();

        $assignments = DB::table('rider_assignments')
            ->where('rider_id', $rider->id)
            ->leftJoin('orders', 'orders.id', '=', 'rider_assignments.order_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->select(
                'rider_assignments.id',
                'rider_assignments.order_id',
                'rider_assignments.status',
                'rider_assignments.created_at',
                'orders.grand_total',
                'orders.payment_method',
                'orders.shipping_address',
                'users.name as customer_name',
                'users.mobile_number as customer_mobile'
            )
            ->orderBy('rider_assignments.created_at', 'desc')
            ->get()
            ->map(function ($a) {
                // Decode shipping_address — it may be a JSON string or already decoded
                $shippingAddr = null;
                if ($a->shipping_address) {
                    $shippingAddr = is_string($a->shipping_address)
                        ? json_decode($a->shipping_address, true)
                        : (array) $a->shipping_address;
                }

                // Prefer phone from shipping address (entered at checkout), fall back to profile mobile
                $phone = $shippingAddr['phone'] ?? $a->customer_mobile ?? null;

                // Prefer full_name from shipping address, fall back to user account name
                $customerName = $shippingAddr['full_name'] ?? $a->customer_name ?? 'Customer';

                return [
                    'id'         => $a->id,
                    'order_id'   => $a->order_id,
                    'status'     => $a->status,
                    'created_at' => $a->created_at,
                    'order'      => [
                        'grand_total'      => $a->grand_total,
                        'payment_method'   => $a->payment_method,
                        'shipping_address' => $shippingAddr,   // decoded array, never a string
                    ],
                    'customer' => [
                        'name'  => $customerName,
                        'phone' => $phone,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $assignments,
        ]);
    }

    /**
     * Update assignment status and synchronize with order status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,picked_up,delivered',
        ]);

        $rider = $request->user();

        $assignment = DB::table('rider_assignments')
            ->where('id', $id)
            ->where('rider_id', $rider->id)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        }

        DB::table('rider_assignments')
            ->where('id', $id)
            ->update([
                'status' => $request->status,
                'updated_at' => now(),
            ]);

        // Update order status too
        if ($request->status === 'delivered') {
            DB::table('orders')
                ->where('id', $assignment->order_id)
                ->update([
                    'status' => 'delivered',
                    'updated_at' => now(),
                ]);

            $order = DB::table('orders')->where('id', $assignment->order_id)->first();
            if ($order) {
                // Requirement 3: Auto-update payment status to 'paid' if it's a COD order
                if (strtolower($order->payment_method) === 'cod') {
                    DB::table('orders')
                        ->where('id', $assignment->order_id)
                        ->update([
                            'payment_status' => 'paid',
                            'updated_at' => now(),
                        ]);
                }

                \App\Helpers\NotificationHelper::send(
                    $order->user_id,
                    'order.delivered',
                    'Order Delivered! 🎉',
                    'Your order #' . str_pad($order->id, 4, '0', STR_PAD_LEFT)
                        . ' has been delivered successfully!',
                    ['url' => '/user/orders']
                );

                \App\Helpers\NotificationHelper::sendToRole(
                    'admin',
                    'order.delivered',
                    'Order Delivered ✅',
                    'Order #' . str_pad($order->id, 4, '0', STR_PAD_LEFT)
                        . ' has been delivered by rider.',
                    ['url' => '/admin/orders']
                );
            }
        } elseif ($request->status === 'picked_up') {
            DB::table('orders')
                ->where('id', $assignment->order_id)
                ->update([
                    'status' => 'shipped',
                    'updated_at' => now(),
                ]);

            $order = DB::table('orders')->where('id', $assignment->order_id)->first();
            if ($order) {
                \App\Helpers\NotificationHelper::send(
                    $order->user_id,
                    'order.picked_up',
                    'Order Picked Up! 🚚',
                    'Your order #' . str_pad($order->id, 4, '0', STR_PAD_LEFT)
                        . ' has been picked up and is on the way!',
                    ['url' => '/user/orders']
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!',
        ]);
    }
}
