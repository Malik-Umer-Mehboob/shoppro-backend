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
    protected $dashboardRepository;

    public function __construct(\App\Repositories\DashboardRepository $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    /**
     * Get admin dashboard stats and recent activity.
     */
    public function stats()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('admin_dashboard_stats', 120, function () {
            $stats = $this->dashboardRepository->getStats();
            
            $orders = $stats['orders'];
            $users = $stats['users'];
            $products = $stats['products'];

            // Growth Calculations
            $ordersGrowth = $orders->last_month_orders > 0 
                ? round((($orders->this_month_orders - $orders->last_month_orders) / $orders->last_month_orders) * 100, 1) 
                : ($orders->this_month_orders > 0 ? 100 : 0);

            $revenueGrowth = $orders->last_month_revenue > 0 
                ? round((($orders->this_month_revenue - $orders->last_month_revenue) / $orders->last_month_revenue) * 100, 1) 
                : ($orders->this_month_revenue > 0 ? 100 : 0);

            $usersGrowth = $users->last_month_users > 0 
                ? round((($users->this_month_users - $users->last_month_users) / $users->last_month_users) * 100, 1) 
                : ($users->this_month_users > 0 ? 100 : 0);

            $productsGrowth = $products->last_month > 0 
                ? round((($products->this_month - $products->last_month) / $products->last_month) * 100, 1) 
                : ($products->this_month > 0 ? 100 : 0);

            // Recent Orders (last 5)
            $recentOrders = Order::with(['user:id,name,email'])
                ->select('id', 'user_id', 'status', 'payment_status', 'grand_total', 'created_at')
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

            return [
                'stats' => [
                    'total_orders' => (int)$orders->total_orders,
                    'orders_growth' => (float)$ordersGrowth,
                    'total_revenue' => (float)$orders->total_revenue,
                    'revenue_growth' => (float)$revenueGrowth,
                    'total_users' => (int)$users->total_users,
                    'new_users' => (int)$users->this_month_users,
                    'users_growth' => (float)$usersGrowth,
                    'total_products' => (int)$products->total,
                    'published_products' => (int)$products->published,
                    'draft_products' => (int)$products->draft,
                    'archived_products' => (int)$products->archived,
                    'approved_products' => (int)$products->approved,
                    'pending_products' => (int)$products->pending,
                    'rejected_products' => (int)$products->rejected,
                    'products_growth' => (float)$productsGrowth,
                ],
                'recent_orders' => $recentOrders,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
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
