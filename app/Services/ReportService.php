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

        $sales = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalSalesAmount = $sales->sum('grand_total');
        $numberOfOrders = $sales->count();
        $averageOrderValue = $numberOfOrders > 0 ? $totalSalesAmount / $numberOfOrders : 0;

        // Top selling products
        $topSellingProducts = DB::table('order_line_items')
            ->join('orders', 'order_line_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', Order::STATUS_CANCELLED)
            ->where('orders.created_at', '>=', $startDate)
            ->select('order_line_items.product_id', 'order_line_items.name', DB::raw('SUM(order_line_items.quantity) as total_quantity'))
            ->groupBy('order_line_items.product_id', 'order_line_items.name')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();

        return [
            'total_sales_amount' => $totalSalesAmount,
            'number_of_orders' => $numberOfOrders,
            'average_order_value' => $averageOrderValue,
            'top_selling_products' => $topSellingProducts,
            'sales_by_payment_method' => $sales->groupBy('payment_method')->map->sum('grand_total'),
        ];
    }

    public function generateInventoryReport()
    {
        $products = Product::with('variants')->get();
        $lowStockProducts = $products->filter(fn($p) => $p->stock_quantity <= 5 && !$p->has_variants);
        $lowStockVariants = $products->filter(fn($p) => $p->has_variants)->flatMap->variants->filter(fn($v) => $v->stock_quantity <= 5);

        $outOfStockProducts = $products->filter(fn($p) => $p->stock_quantity == 0 && !$p->has_variants);

        return [
            'total_products' => $products->count(),
            'low_stock_products_count' => $lowStockProducts->count(),
            'low_stock_variants_count' => $lowStockVariants->count(),
            'out_of_stock_products_count' => $outOfStockProducts->count(),
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
        ];
    }

    public function generateCustomerReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $newCustomers = User::role('customer')->where('created_at', '>=', $startDate)->count();

        // Top customers by spend
        $topCustomers = User::role('customer')
            ->withSum(['orders as total_spend' => function ($query) {
                $query->where('status', '!=', Order::STATUS_CANCELLED);
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

        $orders = Order::where('seller_id', $sellerId)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalSalesAmount = $orders->sum('grand_total');
        $numberOfOrders = $orders->count();

        $topSellingProducts = DB::table('order_line_items')
            ->join('orders', 'order_line_items.order_id', '=', 'orders.id')
            ->where('orders.seller_id', $sellerId)
            ->where('orders.status', '!=', Order::STATUS_CANCELLED)
            ->where('orders.created_at', '>=', $startDate)
            ->select('order_line_items.product_id', 'order_line_items.name', DB::raw('SUM(order_line_items.quantity) as total_quantity'))
            ->groupBy('order_line_items.product_id', 'order_line_items.name')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();

        return [
            'total_sales_amount' => $totalSalesAmount,
            'number_of_orders' => $numberOfOrders,
            'top_selling_products' => $topSellingProducts,
        ];
    }

    public function generateTaxReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $taxCollected = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $startDate)
            ->sum('tax');

        return [
            'total_tax_collected' => $taxCollected,
        ];
    }

    public function generateShippingReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $orders = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'total_shipping_revenue' => $orders->sum('shipping_cost'),
            'orders_by_shipping_method' => $orders->groupBy('shipping_method')->map->count(),
        ];
    }

    public function generateRefundReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $refundedOrders = Order::where('status', Order::STATUS_REFUNDED)
            ->where('updated_at', '>=', $startDate)
            ->get();

        $totalRefundAmount = $refundedOrders->sum('grand_total');
        $totalOrders = Order::where('created_at', '>=', $startDate)->count();

        $refundRate = $totalOrders > 0 ? ($refundedOrders->count() / $totalOrders) * 100 : 0;

        return [
            'total_refund_amount' => $totalRefundAmount,
            'refund_rate' => $refundRate,
            'most_common_reasons' => $refundedOrders->groupBy('refund_reason')->map->count()->sortDesc()->take(5),
        ];
    }

    public function generateCouponReport(string $dateRange = '30')
    {
        $startDate = Carbon::now()->subDays((int)$dateRange);

        $ordersWithCoupons = Order::whereNotNull('coupon_code')
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'orders_with_coupons' => $ordersWithCoupons->count(),
            'total_discount_amount' => $ordersWithCoupons->sum('discount'),
            'most_used_coupons' => $ordersWithCoupons->groupBy('coupon_code')->map->count()->sortDesc()->take(5),
        ];
    }

    public function exportReportAsCSV(array $data, string $filename)
    {
        $path = storage_path('app/public/' . $filename);
        $file = fopen($path, 'w');

        if (!empty($data)) {
            fputcsv($file, array_keys((array)$data[0]));
            foreach ($data as $row) {
                fputcsv($file, (array)$row);
            }
        }

        fclose($file);

        return $path;
    }
}
