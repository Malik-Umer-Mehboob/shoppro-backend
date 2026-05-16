<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('seller')->with('assignedCategories');

        if ($request->status) {
            $query->where('seller_status', $request->status);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('store_name', 'like', '%' . $request->search . '%');
            });
        }

        $sellers = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $sellers
        ]);
    }

    public function show($id)
    {
        $seller = User::role('seller')->with(['assignedCategories', 'products'])->findOrFail($id);
        
        $stats = [
            'total_products' => $seller->products()->count(),
            'total_orders' => DB::table('order_items')->where('seller_id', $seller->id)->distinct('order_id')->count('order_id'),
            'total_revenue' => DB::table('order_items')->where('seller_id', $seller->id)->sum('total'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'seller' => $seller,
                'stats' => $stats
            ]
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,suspended',
            'rejection_reason' => 'required_if:status,rejected|string|nullable',
        ]);

        $seller = User::role('seller')->findOrFail($id);
        
        $updateData = ['seller_status' => $request->status];
        if ($request->status === 'rejected') {
            $updateData['rejection_reason'] = $request->rejection_reason;
        } else {
            $updateData['rejection_reason'] = null; // Clear reason if approved/suspended
        }
        
        $seller->update($updateData);

        // Notify seller
        \App\Services\NotificationService::send(
            $seller->id,
            'seller.status_updated',
            "Seller Account Status Updated: " . ucfirst($request->status) . " 📢",
            "Your seller account status has been updated to {$request->status}." . ($request->rejection_reason ? " Reason: " . $request->rejection_reason : ""),
            [],
            \App\Services\NotificationService::PRIORITY_HIGH,
            '/seller/dashboard'
        );

        return response()->json([
            'success' => true,
            'message' => "Seller status updated to {$request->status}",
            'data' => $seller
        ]);
    }

    public function updateCategories(Request $request, $id)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $seller = User::role('seller')->findOrFail($id);
        $seller->assignedCategories()->sync($request->categories);

        return response()->json([
            'success' => true,
            'message' => 'Seller categories updated successfully',
            'data' => $seller->load('assignedCategories')
        ]);
    }

    /**
     * Delete seller account
     */
    public function destroy(Request $request, $id)
    {
        $seller = User::role('seller')->findOrFail($id);
        
        if ($request->force) {
            $seller->forceDelete();
            $message = 'Seller account permanently deleted';
        } else {
            $seller->delete();
            $message = 'Seller account soft deleted';
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
