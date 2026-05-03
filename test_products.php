<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $query = \App\Models\Product::select(
        'id', 'name', 'slug', 'price', 'sale_price',
        'thumbnail', 'category_id', 'seller_id',
        'stock_quantity', 'status', 'is_featured', 'sku'
    )->with([
        'category:id,name,slug',
    ]);
    $products = $query->latest()->limit(8)->get();
    echo "Products count: " . $products->count() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
