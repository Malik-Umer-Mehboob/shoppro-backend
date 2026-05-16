<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard stats and recent activity.
     */
    public function stats()
    {
        // Total Orders and Growth
        $totalOrders = Order::count();
        $lastMonthOrders = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $thisMonthOrders = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $ordersGrowth = $lastMonthOrders > 0 
            ? round((($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100, 1) 
            : ($thisMonthOrders > 0 ? 100 : 0);

        // Total Revenue and Growth
        $totalRevenue = Order::revenue()->sum('grand_total');
        $lastMonthRevenue = Order::revenue()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('grand_total');
        $thisMonthRevenue = Order::revenue()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('grand_total');
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) 
            : ($thisMonthRevenue > 0 ? 100 : 0);

        // New Users (this month) and Growth
        $totalUsers = User::whereDoesntHave('roles', function($q) {
            $q->where('name', 'admin');
        })->count();
        $lastMonthUsers = User::whereDoesntHave('roles', function($q) {
            $q->where('name', 'admin');
        })->whereMonth('created_at', now()->subMonth()->month)
          ->whereYear('created_at', now()->subMonth()->year)
          ->count();
        $thisMonthUsers = User::whereDoesntHave('roles', function($q) {
            $q->where('name', 'admin');
        })->whereMonth('created_at', now()->month)
          ->whereYear('created_at', now()->year)
          ->count();
        $usersGrowth = $lastMonthUsers > 0 
            ? round((($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 1) 
            : ($thisMonthUsers > 0 ? 100 : 0);

        // Total Products and Growth (Simplified: Product::query() excludes soft deleted by default)
        $totalProducts = Product::count();
        $publishedProducts = Product::where('status', 'published')->count();
        $draftProducts = Product::where('status', 'draft')->count();
        $archivedProducts = Product::where('status', 'archived')->count();
        $approvedProducts = Product::where('moderation_status', 'approved')->count();
        $pendingProducts = Product::where('moderation_status', 'pending')->count();
        $rejectedProducts = Product::where('moderation_status', 'rejected')->count();

        $lastMonthProducts = Product::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $thisMonthProducts = Product::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $productsGrowth = $lastMonthProducts > 0 
            ? round((($thisMonthProducts - $lastMonthProducts) / $lastMonthProducts) * 100, 1) 
            : ($thisMonthProducts > 0 ? 100 : 0);

        // Recent Orders (last 5)
        $recentOrders = Order::with(['user'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => '#' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'customer_name' => $order->user?->name ?? 'Guest',
                    'customer_email' => $order->user?->email ?? '',
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'grand_total' => $order->grand_total,
                    'created_at' => $order->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_orders' => $totalOrders,
                    'orders_growth' => (float)$ordersGrowth,
                    'total_revenue' => (float)$totalRevenue,
                    'revenue_growth' => (float)$revenueGrowth,
                    'total_users' => $totalUsers,
                    'new_users' => $thisMonthUsers,
                    'users_growth' => (float)$usersGrowth,
                    'total_products' => $totalProducts,
                    'published_products' => $publishedProducts,
                    'draft_products' => $draftProducts,
                    'archived_products' => $archivedProducts,
                    'approved_products' => $approvedProducts,
                    'pending_products' => $pendingProducts,
                    'rejected_products' => $rejectedProducts,
                    'products_growth' => (float)$productsGrowth,
                ],
                'recent_orders' => $recentOrders,
            ]
        ]);
    }

    /**
     * Get daily sales chart data for the last N days.
     */
    public function salesChart(Request $request)
    {
        $days = max(7, min(90, (int)$request->get('days', 30)));
        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
        $endDate   = Carbon::today()->endOfDay();

        // Query grouped by date
        $rows = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(grand_total) as revenue')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Build a complete date range filling zeros for days with no orders
        $labels      = [];
        $revenues    = [];
        $orderCounts = [];

        for ($i = 0; $i < $days; $i++) {
            $date   = Carbon::today()->subDays($days - 1 - $i)->toDateString();
            $label  = Carbon::parse($date)->format('M j');
            $labels[]      = $label;
            $revenues[]    = isset($rows[$date]) ? (float)$rows[$date]->revenue    : 0;
            $orderCounts[] = isset($rows[$date]) ? (int)$rows[$date]->order_count  : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels'       => $labels,
                'revenue'      => $revenues,
                'order_count'  => $orderCounts,
                'days'         => $days,
            ]
        ]);
    }
}
