<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\BlogPost;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function generate()
    {
        $sitemap = Sitemap::create();

        // Static pages
        $staticPages = [
            '/',
            '/login',
            '/register',
            '/cart',
            '/wishlist',
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create(env('FRONTEND_URL', 'http://localhost:5173') . $page)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        }

        // Product pages
        Product::where('status', 'published')
            ->select('id', 'slug', 'updated_at')
            ->chunk(100, function ($products) use ($sitemap) {
                foreach ($products as $product) {
                    $sitemap->add(
                        Url::create(
                            env('FRONTEND_URL', 'http://localhost:5173') . '/products/' . $product->id
                        )
                        ->setLastModificationDate($product->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.9)
                    );
                }
            });

        // Category pages
        Category::select('id', 'slug', 'updated_at')
            ->chunk(50, function ($categories) use ($sitemap) {
                foreach ($categories as $category) {
                    $sitemap->add(
                        Url::create(
                            env('FRONTEND_URL', 'http://localhost:5173') . '/search?category=' . $category->id
                        )
                        ->setLastModificationDate($category->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.7)
                    );
                }
            });

        // Blog posts
        if (class_exists(BlogPost::class)) {
            BlogPost::where('status', 'published')
                ->select('id', 'slug', 'updated_at')
                ->chunk(50, function ($posts) use ($sitemap) {
                    foreach ($posts as $post) {
                        $sitemap->add(
                            Url::create(
                                env('FRONTEND_URL', 'http://localhost:5173') . '/blog/' . $post->slug
                            )
                            ->setLastModificationDate($post->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.6)
                        );
                    }
                });
        }

        return $sitemap->toResponse(request());
    }
}
