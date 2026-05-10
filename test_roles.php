<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "Total users: " . App\Models\User::count() . "\n";
echo "Spatie Customer: " . App\Models\User::whereHas('roles', function($q) { $q->where('name', 'customer'); })->count() . "\n";
echo "String Customer: " . App\Models\User::where('role', 'customer')->count() . "\n";

echo "Spatie Admin: " . App\Models\User::whereHas('roles', function($q) { $q->where('name', 'admin'); })->count() . "\n";
echo "String Admin: " . App\Models\User::where('role', 'admin')->count() . "\n";
