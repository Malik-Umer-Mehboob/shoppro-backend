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
        $cacheKey = "seller_dashboard_stats_{$seller->id}";

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($seller) {
            $assignedCategories = $seller->assignedCategories()->get(['categories.id', 'categories.name']);

            // 1. Product Stats (Aggregated)
            $productStats = Product::where('seller_id', $seller->id)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
                ")->first();

            // 2. Order & Revenue Stats (Aggregated)
            $orderStats = OrderItem::where('order_items.seller_id', $seller->id)
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->selectRaw("
                    COUNT(DISTINCT order_items.order_id) as total_orders,
                    COUNT(DISTINCT CASE WHEN orders.created_at >= '" . now()->startOfMonth() . "' THEN order_items.order_id END) as this_month_orders,
                    SUM(CASE WHEN orders.status != 'cancelled' AND orders.payment_status = 'paid' THEN order_items.total ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN orders.status != 'cancelled' AND orders.payment_status = 'paid' AND orders.created_at >= '" . now()->startOfMonth() . "' THEN order_items.total ELSE 0 END) as this_month_revenue
                ")->first();

            // 3. Recent orders
            $recentOrders = OrderItem::with(['order:id,status,created_at,user_id', 'order.user:id,name', 'product:id,name'])
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

            // 4. Low stock products
            $lowStockProducts = Product::where('seller_id', $seller->id)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->where('stock_quantity', '>', 0)
                ->take(5)
                ->get(['id', 'name', 'stock_quantity', 'low_stock_threshold', 'sku']);

            return [
                'seller_name' => $seller->name,
                'account_status' => $seller->seller_status,
                'stats' => [
                    'total_products' => (int)$productStats->total,
                    'published_products' => (int)$productStats->published,
                    'draft_products' => (int)$productStats->draft,
                    'approved_products' => (int)$productStats->approved,
                    'pending_products' => (int)$productStats->pending,
                    'rejected_products' => (int)$productStats->rejected,
                    'total_orders' => (int)$orderStats->total_orders,
                    'this_month_orders' => (int)$orderStats->this_month_orders,
                    'total_revenue' => (float)$orderStats->total_revenue,
                    'this_month_revenue' => (float)$orderStats->this_month_revenue,
                    'out_of_stock' => (int)$productStats->out_of_stock,
                ],
                'recent_orders' => $recentOrders,
                'low_stock_products' => $lowStockProducts,
                'assigned_categories' => $assignedCategories,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
