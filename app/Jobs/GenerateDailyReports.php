<?php

namespace App\Jobs;

use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class GenerateDailyReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        // Generate daily reports
        $salesReport = $reportService->generateMonthlySalesReport('1');
        $inventoryReport = $reportService->generateInventoryReport();

        // Normally, we would email these, or save to disk.
        // For demonstration, let's export to CSV and log it or send email to admin.
        
        $adminEmail = config('mail.from.address', 'admin@shoppro.com');
        
        Mail::raw("Daily Sales Report:\n\nTotal Sales: $" . $salesReport['total_sales_amount'] . "\nOrders: " . $salesReport['number_of_orders'] . "\n\nLow Stock Products: " . $inventoryReport['low_stock_products_count'], function ($message) use ($adminEmail) {
            $message->to($adminEmail)
                    ->subject('ShopPro Daily Summary Report');
        });
    }
}
