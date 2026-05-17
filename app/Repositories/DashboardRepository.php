<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardRepository
{
    /**
     * Get aggregated stats in optimized queries.
     */
    public function getStats(): array
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // 1. Order & Revenue Stats (Aggregated)
        $orderStats = Order::selectRaw("
            COUNT(*) as total_orders,
            SUM(CASE WHEN created_at >= '{$thisMonthStart}' THEN 1 ELSE 0 END) as this_month_orders,
            SUM(CASE WHEN created_at >= '{$lastMonthStart}' AND created_at <= '{$lastMonthEnd}' THEN 1 ELSE 0 END) as last_month_orders,
            SUM(grand_total) as total_revenue,
            SUM(CASE WHEN created_at >= '{$thisMonthStart}' THEN grand_total ELSE 0 END) as this_month_revenue,
            SUM(CASE WHEN created_at >= '{$lastMonthStart}' AND created_at <= '{$lastMonthEnd}' THEN grand_total ELSE 0 END) as last_month_revenue
        ")->first();

        // 2. User Stats (Aggregated)
        $userStats = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->selectRaw("
                COUNT(*) as total_users,
                SUM(CASE WHEN created_at >= '{$thisMonthStart}' THEN 1 ELSE 0 END) as this_month_users,
                SUM(CASE WHEN created_at >= '{$lastMonthStart}' AND created_at <= '{$lastMonthEnd}' THEN 1 ELSE 0 END) as last_month_users
            ")->first();

        // 3. Product Stats (Aggregated)
        $productStats = Product::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
            SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN created_at >= '{$thisMonthStart}' THEN 1 ELSE 0 END) as this_month,
            SUM(CASE WHEN created_at >= '{$lastMonthStart}' AND created_at <= '{$lastMonthEnd}' THEN 1 ELSE 0 END) as last_month
        ")->first();

        return [
            'orders' => $orderStats,
            'users' => $userStats,
            'products' => $productStats
        ];
    }
}
