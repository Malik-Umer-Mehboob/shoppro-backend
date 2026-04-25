<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerAnalyticsController extends Controller
{
    public function index()
    {
        $seller = auth()->user();
        $sellerProductIds = Product::where('seller_id', $seller->id)->pluck('id');

        // Monthly revenue for last 6 months
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = OrderItem::whereIn('product_id', $sellerProductIds)
                ->whereHas('order', function($q) use ($month) {
                    $q->where('payment_status', 'paid')
                      ->whereYear('created_at', $month->year)
                      ->whereMonth('created_at', $month->month);
                })
                ->sum('total');
            
            $monthlyRevenue[] = [
                'month' => $month->format('M Y'),
                'revenue' => round($revenue, 2),
            ];
        }

        // Top selling products
        $topProducts = OrderItem::whereIn('product_id', $sellerProductIds)
            ->select('product_id', 
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->with('product:id,name,thumbnail,price')
            ->get()
            ->map(function($item) {
                return [
                    'product_name' => $item->product->name ?? 'N/A',
                    'total_sold' => (int)$item->total_sold,
                    'total_revenue' => (float)$item->total_revenue,
                ];
            });

        // Orders by status
        $ordersByStatus = OrderItem::whereIn('product_id', $sellerProductIds)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('orders.status', DB::raw('COUNT(DISTINCT orders.id) as count'))
            ->groupBy('orders.status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'monthly_revenue' => $monthlyRevenue,
                'top_products' => $topProducts,
                'orders_by_status' => $ordersByStatus,
            ]
        ]);
    }
}
