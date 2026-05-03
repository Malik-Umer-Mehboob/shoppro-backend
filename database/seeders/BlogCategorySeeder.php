<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'General',
            'Fashion & Style',
            'Technology',
            'Home & Living',
            'Health & Beauty',
            'Deals & Offers',
            'Shopping Tips',
            'New Arrivals',
        ];

        foreach ($categories as $name) {
            DB::table('blog_categories')->insertOrIgnore([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $name . ' related articles',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
