<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Electronics' => [
                'Mobile Phones',
                'Laptops',
                'Tablets',
                'Accessories',
            ],
            'Fashion' => [
                "Men's Clothing",
                "Women's Clothing",
                "Kids' Clothing",
                'Shoes',
            ],
            'Home & Living' => [
                'Furniture',
                'Kitchen',
                'Bedding',
                'Decor',
            ],
            'Sports' => [
                'Cricket',
                'Football',
                'Gym & Fitness',
                'Outdoor',
            ],
            'Beauty' => [
                'Skincare',
                'Haircare',
                'Makeup',
                'Fragrances',
            ],
        ];

        foreach ($categories as $parent => $children) {
            $parentCategory = Category::create([
                'name' => $parent,
                'slug' => Str::slug($parent),
                'is_active' => true,
            ]);

            foreach ($children as $child) {
                Category::create([
                    'name' => $child,
                    'slug' => Str::slug($child),
                    'parent_id' => $parentCategory->id,
                    'is_active' => true,
                ]);
            }
        }
    }
}
