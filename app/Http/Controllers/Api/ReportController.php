<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function sales(Request $request)
    {
        $days = $request->query('days', '30');
        return response()->json($this->reportService->generateMonthlySalesReport($days));
    }

    public function inventory()
    {
        return response()->json($this->reportService->generateInventoryReport());
    }

    public function customers(Request $request)
    {
        $days = $request->query('days', '30');
        return response()->json($this->reportService->generateCustomerReport($days));
    }

    public function seller(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('seller')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $days = $request->query('days', '30');
        return response()->json($this->reportService->generateSellerReport($user->id, $days));
    }

    public function taxes(Request $request)
    {
        $days = $request->query('days', '30');
        return response()->json($this->reportService->generateTaxReport($days));
    }

    public function shipping(Request $request)
    {
        $days = $request->query('days', '30');
        return response()->json($this->reportService->generateShippingReport($days));
    }

    public function refunds(Request $request)
    {
        $days = $request->query('days', '30');
        return response()->json($this->reportService->generateRefundReport($days));
    }

    public function coupons(Request $request)
    {
        $days = $request->query('days', '30');
        return response()->json($this->reportService->generateCouponReport($days));
    }

    public function export(Request $request)
    {
        $type = $request->query('type');
        $days = $request->query('days', '30');
        $data = [];
        $filename = 'export.csv';

        switch ($type) {
            case 'sales':
                $report = $this->reportService->generateMonthlySalesReport($days);
                // Flatten data for CSV
                $data = [
                    ['Metric' => 'Total Sales', 'Value' => $report['total_sales_amount']],
                    ['Metric' => 'Number of Orders', 'Value' => $report['number_of_orders']],
                    ['Metric' => 'Average Order Value', 'Value' => $report['average_order_value']],
                ];
                $filename = "sales_report_{$days}days.csv";
                break;
            case 'inventory':
                $report = $this->reportService->generateInventoryReport();
                $data = [
                    ['Metric' => 'Total Products', 'Value' => $report['total_products']],
                    ['Metric' => 'Low Stock Products', 'Value' => $report['low_stock_products_count']],
                    ['Metric' => 'Out of Stock Products', 'Value' => $report['out_of_stock_products_count']],
                ];
                $filename = "inventory_report.csv";
                break;
            // More cases can be handled similarly
            default:
                return response()->json(['message' => 'Invalid report type'], 400);
        }

        $path = $this->reportService->exportReportAsCSV($data, $filename);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
