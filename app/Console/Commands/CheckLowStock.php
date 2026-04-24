<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:low-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find low stock products and variants and notify admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lowStockProducts = \App\Models\Product::whereRaw('stock_quantity <= low_stock_threshold')->get();
        $lowStockVariants = \App\Models\ProductVariant::with('product')->where('stock_quantity', '<=', 5)->get();

        if ($lowStockProducts->isEmpty() && $lowStockVariants->isEmpty()) {
            $this->info('No low stock items found.');
            return;
        }

        $data = [];

        foreach ($lowStockProducts as $product) {
            $data[] = [
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => $product->stock_quantity,
                'threshold' => $product->low_stock_threshold
            ];
        }

        foreach ($lowStockVariants as $variant) {
            $data[] = [
                'name' => $variant->product->name . ' (' . $variant->size . '/' . $variant->color . ')',
                'sku' => $variant->sku,
                'stock' => $variant->stock_quantity,
                'threshold' => 5
            ];
        }

        \Illuminate\Support\Facades\Mail::to('malik.umerkhan97@gmail.com')
            ->send(new \App\Mail\LowStockMail($data));

        $this->info('Low stock alert sent to admin.');
    }
}
