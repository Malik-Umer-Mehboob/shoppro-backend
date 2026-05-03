<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $languages = Illuminate\Support\Facades\Cache::remember('languages', 3600, function () {
        return Illuminate\Support\Facades\DB::table('languages')
            ->where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();
    });
    echo "Languages count: " . count($languages) . "\n";
    foreach ($languages as $lang) {
        echo "- {$lang->name} ({$lang->code})\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
