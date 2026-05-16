<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class StaffManagementController extends Controller
{
    /**
     * List all support staff
     */
    public function indexSupport()
    {
        $support = User::role('support')->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $support
        ]);
    }

    /**
     * Create new support staff account
     */
    public function storeSupport(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'support',
            'staff_status' => 'active',
        ]);

        $user->assignRole('support');

        return response()->json([
            'success' => true,
            'message' => 'Support account created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * List all riders
     */
    public function indexRiders()
    {
        $riders = User::role('rider')->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $riders
        ]);
    }

    /**
     * Create new rider account
     */
    public function storeRider(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'mobile_number' => ['required', 'string'],
            'vehicle_type' => ['required', 'string'],
            'cnic' => ['required', 'string', 'digits:13'],
            'delivery_zone' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mobile_number' => $request->mobile_number,
            'vehicle_type' => $request->vehicle_type,
            'cnic' => $request->cnic,
            'delivery_zone' => $request->delivery_zone,
            'role' => 'rider',
            'staff_status' => 'active',
        ]);

        $user->assignRole('rider');

        return response()->json([
            'success' => true,
            'message' => 'Rider account created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Update staff account
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'staff_status' => ['required', 'in:active,inactive'],
        ];

        if ($user->hasRole('rider')) {
            $rules['mobile_number'] = ['required', 'string'];
            $rules['vehicle_type'] = ['required', 'string'];
            $rules['cnic'] = ['required', 'string', 'digits:13'];
            $rules['delivery_zone'] = ['required', 'string'];
        }

        $request->validate($rules);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'staff_status' => $request->staff_status,
        ];

        if ($user->hasRole('rider')) {
            $data['mobile_number'] = $request->mobile_number;
            $data['vehicle_type'] = $request->vehicle_type;
            $data['cnic'] = $request->cnic;
            $data['delivery_zone'] = $request->delivery_zone;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Delete staff account
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully'
        ]);
    }

    /**
     * Toggle staff status
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->staff_status = $user->staff_status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Staff status updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Get staff performance metrics
     */
    public function getStaffMetrics()
    {
        $supportCount = User::role('support')->count();
        $riderCount = User::role('rider')->count();
        
        $activeRiders = User::role('rider')->where('staff_status', 'active')->count();
        $activeSupport = User::role('support')->where('staff_status', 'active')->count();

        // Add more metrics as needed
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_support' => $supportCount,
                'total_riders' => $riderCount,
                'active_support' => $activeSupport,
                'active_riders' => $activeRiders,
            ]
        ]);
    }
    /**
     * Get rider details and statistics
     */
    public function showRider($id)
    {
        $rider = User::role('rider')->findOrFail($id);

        $stats = [
            'total_assigned' => \App\Models\RiderAssignment::where('rider_id', $id)->count(),
            'delivered_count' => \App\Models\RiderAssignment::where('rider_id', $id)->where('status', 'delivered')->count(),
            'pending_count' => \App\Models\RiderAssignment::where('rider_id', $id)->whereIn('status', ['pending', 'assigned'])->count(),
            'processing_count' => \App\Models\RiderAssignment::where('rider_id', $id)->where('status', 'picked_up')->count(),
        ];

        $recentDeliveries = \App\Models\RiderAssignment::with(['order.user'])
            ->where('rider_id', $id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'order_id' => $assignment->order_id,
                    'order_number' => '#' . str_pad($assignment->order_id, 4, '0', STR_PAD_LEFT),
                    'customer_name' => $assignment->order->user->name ?? 'Guest',
                    'grand_total' => $assignment->order->grand_total,
                    'status' => $assignment->status,
                    'assigned_at' => $assignment->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'rider' => $rider,
                'stats' => $stats,
                'recent_deliveries' => $recentDeliveries
            ]
        ]);
    }
}
