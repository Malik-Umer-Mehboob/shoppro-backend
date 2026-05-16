<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $seller = auth()->user();
        $assignedCategories = $seller->assignedCategories()->get(['categories.id', 'categories.name']);

        $totalProducts = Product::where('seller_id', $seller->id)->count();
        $publishedProducts = Product::where('seller_id', $seller->id)
            ->where('status', 'published')->count();
        $draftProducts = Product::where('seller_id', $seller->id)
            ->where('status', 'draft')->count();
        
        // Moderation Status counts
        $approvedProducts = Product::where('seller_id', $seller->id)
            ->where('moderation_status', 'approved')->count();
        $pendingProducts = Product::where('seller_id', $seller->id)
            ->where('moderation_status', 'pending')->count();
        $rejectedProducts = Product::where('seller_id', $seller->id)
            ->where('moderation_status', 'rejected')->count();

        // Total orders containing seller's products
        $totalOrders = OrderItem::where('seller_id', $seller->id)
            ->distinct('order_id')
            ->count('order_id');

        $thisMonthOrders = OrderItem::where('seller_id', $seller->id)
            ->whereHas('order', function($q) {
                $q->whereMonth('created_at', now()->month);
            })
            ->distinct('order_id')
            ->count('order_id');

        // Total revenue from seller's products
        $totalRevenue = OrderItem::where('seller_id', $seller->id)
            ->whereHas('order', function($q) {
                $q->revenue();
            })
            ->sum('total');

        $thisMonthRevenue = OrderItem::where('seller_id', $seller->id)
            ->whereHas('order', function($q) {
                $q->revenue()
                  ->whereMonth('created_at', now()->month);
            })
            ->sum('total');

        // Recent orders
        $recentOrders = OrderItem::with(['order.user', 'product'])
            ->where('seller_id', $seller->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function($item) {
                return [
                    'order_number' => '#' . str_pad($item->order_id, 4, '0', STR_PAD_LEFT),
                    'product_name' => $item->product->name ?? 'N/A',
                    'customer_name' => $item->order?->user?->name ?? 'Guest',
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'status' => $item->order->status,
                    'created_at' => $item->order->created_at->diffForHumans(),
                ];
            });

        // Low stock products
        $lowStockProducts = Product::where('seller_id', $seller->id)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->take(5)
            ->get(['id', 'name', 'stock_quantity', 'low_stock_threshold', 'sku']);

        $outOfStock = Product::where('seller_id', $seller->id)
            ->where('stock_quantity', 0)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'seller_name' => $seller->name,
                'account_status' => $seller->seller_status,
                'stats' => [
                    'total_products' => $totalProducts,
                    'published_products' => $publishedProducts,
                    'draft_products' => $draftProducts,
                    'approved_products' => $approvedProducts,
                    'pending_products' => $pendingProducts,
                    'rejected_products' => $rejectedProducts,
                    'total_orders' => $totalOrders,
                    'this_month_orders' => $thisMonthOrders,
                    'total_revenue' => $totalRevenue,
                    'this_month_revenue' => $thisMonthRevenue,
                    'out_of_stock' => $outOfStock,
                ],
                'recent_orders' => $recentOrders,
                'low_stock_products' => $lowStockProducts,
                'assigned_categories' => $assignedCategories,
            ]
        ]);
    }
}
