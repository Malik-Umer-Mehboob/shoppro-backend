<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomepageController extends Controller
{
    protected $productRepository;

    public function __construct(\App\Repositories\ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Get optimized homepage data in a single request.
     */
    public function index(Request $request)
    {
        $data = Cache::remember('homepage_data', 3600, function () {
            $featuredProducts = $this->productRepository->getHomepageProducts(8);

            return [
                'featured_products' => \App\Http\Resources\ProductResource::collection($featuredProducts),
            ];
        });

        return $this->success($data, 'Homepage data retrieved successfully');
    }
}
