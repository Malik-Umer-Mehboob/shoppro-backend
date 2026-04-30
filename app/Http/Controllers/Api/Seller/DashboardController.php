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

        // Total products by this seller
        $totalProducts = Product::where('seller_id', $seller->id)->count();
        $publishedProducts = Product::where('seller_id', $seller->id)
            ->where('status', 'published')->count();
        $draftProducts = Product::where('seller_id', $seller->id)
            ->where('status', 'draft')->count();

        // Total orders containing seller's products
        $sellerProductIds = Product::where('seller_id', $seller->id)
            ->pluck('id');

        $totalOrders = OrderItem::whereIn('product_id', $sellerProductIds)
            ->distinct('order_id')
            ->count('order_id');

        $thisMonthOrders = OrderItem::whereIn('product_id', $sellerProductIds)
            ->whereHas('order', function($q) {
                $q->whereMonth('created_at', now()->month);
            })
            ->distinct('order_id')
            ->count('order_id');

        // Total revenue from seller's products
        $totalRevenue = OrderItem::whereIn('product_id', $sellerProductIds)
            ->whereHas('order', function($q) {
                $q->where('payment_status', 'paid');
            })
            ->sum('total');

        $thisMonthRevenue = OrderItem::whereIn('product_id', $sellerProductIds)
            ->whereHas('order', function($q) {
                $q->where('payment_status', 'paid')
                  ->whereMonth('created_at', now()->month);
            })
            ->sum('total');

        // Recent orders
        $recentOrders = OrderItem::with(['order.user', 'product'])
            ->whereIn('product_id', $sellerProductIds)
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
                'stats' => [
                    'total_products' => $totalProducts,
                    'published_products' => $publishedProducts,
                    'draft_products' => $draftProducts,
                    'total_orders' => $totalOrders,
                    'this_month_orders' => $thisMonthOrders,
                    'total_revenue' => $totalRevenue,
                    'this_month_revenue' => $thisMonthRevenue,
                    'out_of_stock' => $outOfStock,
                ],
                'recent_orders' => $recentOrders,
                'low_stock_products' => $lowStockProducts,
            ]
        ]);
    }
}
