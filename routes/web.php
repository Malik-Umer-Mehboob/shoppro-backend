<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SitemapController;

Route::get('/', function () {
    return response()->json(['message' => 'ShopPro API Running']);
});

Route::get('/sitemap.xml', [SitemapController::class, 'generate']);