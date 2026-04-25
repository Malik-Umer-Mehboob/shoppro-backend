<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            : 0;

        // Total Revenue and Growth
        $totalRevenue = Order::where('payment_status', 'paid')->sum('grand_total');
        $lastMonthRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('grand_total');
        $thisMonthRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('grand_total');
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) 
            : 0;

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
            : 0;

        // Total Products and Growth
        $totalProducts = Product::count();
        $lastMonthProducts = Product::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $thisMonthProducts = Product::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $productsGrowth = $lastMonthProducts > 0 
            ? round((($thisMonthProducts - $lastMonthProducts) / $lastMonthProducts) * 100, 1) 
            : 0;

        // Recent Orders (last 5)
        $recentOrders = Order::with(['user'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => '#' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'customer_name' => $order->user->name ?? 'Guest',
                    'customer_email' => $order->user->email ?? '',
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
                    'new_users' => $thisMonthUsers,
                    'users_growth' => (float)$usersGrowth,
                    'total_products' => $totalProducts,
                    'products_growth' => (float)$productsGrowth,
                ],
                'recent_orders' => $recentOrders,
            ]
        ]);
    }
}
