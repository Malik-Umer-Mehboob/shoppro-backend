<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReportService
{
    public function generateMonthlySalesReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $stats = Order::revenue()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('SUM(grand_total) as total_revenue, COUNT(*) as order_count')
            ->first();

        $totalSalesAmount = $stats->total_revenue ?? 0;
        $numberOfOrders = $stats->order_count ?? 0;
        $averageOrderValue = $numberOfOrders > 0 ? $totalSalesAmount / $numberOfOrders : 0;

        // Top selling products (Optimized query)
        $topSellingProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', Order::PAYMENT_PAID)
            ->where('orders.status', '!=', Order::STATUS_CANCELLED)
            ->where('orders.created_at', '>=', $startDate)
            ->select('order_items.product_id', 'order_items.name', DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->groupBy('order_items.product_id', 'order_items.name')
            ->having('total_quantity', '>', 0)
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();

        // Payment method breakdown (Aggregated in DB)
        $paymentBreakdown = Order::revenue()
            ->where('created_at', '>=', $startDate)
            ->select('payment_method', DB::raw('SUM(grand_total) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return [
            'total_sales_amount' => (float)$totalSalesAmount,
            'number_of_orders' => (int)$numberOfOrders,
            'average_order_value' => (float)$averageOrderValue,
            'top_selling_products' => $topSellingProducts,
            'sales_by_payment_method' => $paymentBreakdown,
        ];
    }

    public function generateInventoryReport()
    {
        // Avoid getting all products and variants into memory
        $totalProducts = Product::count();
        $lowStockProductsCount = Product::where('stock_quantity', '<=', 5)->where('has_variants', false)->count();
        $outOfStockProductsCount = Product::where('stock_quantity', 0)->where('has_variants', false)->count();
        
        $lowStockVariantsCount = DB::table('product_variants')->where('stock_quantity', '<=', 5)->count();

        // Only load the actual lists for the first few items or use pagination if needed
        $lowStockProducts = Product::where('stock_quantity', '<=', 5)->where('has_variants', false)->limit(50)->get();
        $outOfStockProducts = Product::where('stock_quantity', 0)->where('has_variants', false)->limit(50)->get();

        return [
            'total_products' => $totalProducts,
            'low_stock_products_count' => $lowStockProductsCount,
            'low_stock_variants_count' => $lowStockVariantsCount,
            'out_of_stock_products_count' => $outOfStockProductsCount,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
        ];
    }

    public function generateCustomerReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $newCustomers = User::role('customer')->where('created_at', '>=', $startDate)->count();

        // Top customers by spend (Already relatively optimized but ensure select)
        $topCustomers = User::role('customer')
            ->select('id', 'name', 'email')
            ->withSum(['orders as total_spend' => function ($query) {
                $query->revenue();
            }], 'grand_total')
            ->orderByDesc('total_spend')
            ->take(5)
            ->get();

        return [
            'new_customers_this_period' => $newCustomers,
            'top_customers' => $topCustomers,
        ];
    }

    public function generateSellerReport(int $sellerId, string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $stats = Order::where('seller_id', $sellerId)
            ->revenue()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('SUM(grand_total) as total_revenue, COUNT(*) as order_count')
            ->first();

        $topSellingProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.seller_id', $sellerId)
            ->where('orders.payment_status', Order::PAYMENT_PAID)
            ->where('orders.status', '!=', Order::STATUS_CANCELLED)
            ->where('orders.created_at', '>=', $startDate)
            ->select('order_items.product_id', 'order_items.name', DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->groupBy('order_items.product_id', 'order_items.name')
            ->having('total_quantity', '>', 0)
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();

        return [
            'total_sales_amount' => (float)($stats->total_revenue ?? 0),
            'number_of_orders' => (int)($stats->order_count ?? 0),
            'top_selling_products' => $topSellingProducts,
        ];
    }

    public function generateTaxReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $taxCollected = Order::revenue()
            ->where('created_at', '>=', $startDate)
            ->sum('tax');

        return [
            'total_tax_collected' => (float)$taxCollected,
        ];
    }

    public function generateShippingReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $stats = Order::revenue()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('SUM(shipping_cost) as total_revenue')
            ->first();

        $methodBreakdown = Order::revenue()
            ->where('created_at', '>=', $startDate)
            ->select('shipping_method', DB::raw('COUNT(*) as count'))
            ->groupBy('shipping_method')
            ->pluck('count', 'shipping_method');

        return [
            'total_shipping_revenue' => (float)($stats->total_revenue ?? 0),
            'orders_by_shipping_method' => $methodBreakdown,
        ];
    }

    public function generateRefundReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $refundStats = Order::where('status', Order::STATUS_REFUNDED)
            ->where('updated_at', '>=', $startDate)
            ->selectRaw('SUM(grand_total) as total_amount, COUNT(*) as count')
            ->first();

        $totalOrders = Order::where('created_at', '>=', $startDate)->count();

        $refundRate = $totalOrders > 0 ? (($refundStats->count ?? 0) / $totalOrders) * 100 : 0;

        return [
            'total_refund_amount' => (float)($refundStats->total_amount ?? 0),
            'refund_rate' => (float)$refundRate,
            'most_common_reasons' => [], // Requires a separate efficient query if needed
        ];
    }

    public function generateCouponReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $stats = Order::whereNotNull('coupon_code')
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('COUNT(*) as count, SUM(discount) as total_discount')
            ->first();

        return [
            'orders_with_coupons' => (int)($stats->count ?? 0),
            'total_discount_amount' => (float)($stats->total_discount ?? 0),
            'most_used_coupons' => [], // Requires separate query
        ];
    }

    public function getDetailedSalesData(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        // Return a LazyCollection/Generator for efficient CSV writing
        return Order::with('user:id,name')
            ->revenue()
            ->where('created_at', '>=', $startDate)
            ->latest()
            ->cursor()
            ->map(function ($order) {
                return [
                    'Order ID' => $order->id,
                    'Order Date' => $order->created_at->format('Y-m-d H:i'),
                    'Customer Name' => $order->user?->name ?? 'Guest',
                    'Total Amount' => $order->grand_total,
                    'Payment Status' => $order->payment_status,
                    'Order Status' => $order->status,
                    'Payment Method' => $order->payment_method,
                ];
            });
    }

    public function exportReportAsCSV($data, string $filename)
    {
        $path = storage_path('app/public/' . $filename);
        $file = fopen($path, 'w');
        fputs($file, "\xEF\xBB\xBF"); // Add BOM

        $isFirst = true;

        foreach ($data as $row) {
            $rowArray = (array)$row;
            if ($isFirst) {
                fputcsv($file, array_keys($rowArray));
                $isFirst = false;
            }
            fputcsv($file, $rowArray);
        }

        fclose($file);

        return $path;
    }
}
