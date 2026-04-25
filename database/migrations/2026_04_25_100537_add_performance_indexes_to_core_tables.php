<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products
        Schema::table('products', function (Blueprint $table) {
            if (!$this->hasIndex('products', 'products_status_index')) $table->index('status');
            if (!$this->hasIndex('products', 'products_is_featured_index')) $table->index('is_featured');
            if (!$this->hasIndex('products', 'products_category_id_index')) $table->index('category_id');
            if (!$this->hasIndex('products', 'products_seller_id_index')) $table->index('seller_id');
            if (!$this->hasIndex('products', 'products_price_index')) $table->index('price');
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->hasIndex('orders', 'orders_status_index')) $table->index('status');
            if (!$this->hasIndex('orders', 'orders_user_id_index')) $table->index('user_id');
            if (!$this->hasIndex('orders', 'orders_created_at_index')) $table->index('created_at');
        });

        // Categories
        Schema::table('categories', function (Blueprint $table) {
            if (!$this->hasIndex('categories', 'categories_slug_index')) $table->index('slug');
            if (!$this->hasIndex('categories', 'categories_parent_id_index')) $table->index('parent_id');
        });

        // Reviews
        Schema::table('reviews', function (Blueprint $table) {
            if (!$this->hasIndex('reviews', 'reviews_product_id_index')) $table->index('product_id');
            if (!$this->hasIndex('reviews', 'reviews_rating_index')) $table->index('rating');
        });

        // Cart Items
        Schema::table('cart_items', function (Blueprint $table) {
            if (!$this->hasIndex('cart_items', 'cart_items_cart_id_index')) $table->index('cart_id');
            if (!$this->hasIndex('cart_items', 'cart_items_product_id_index')) $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if ($this->hasIndex('products', 'products_status_index')) $table->dropIndex(['status']);
            if ($this->hasIndex('products', 'products_is_featured_index')) $table->dropIndex(['is_featured']);
            if ($this->hasIndex('products', 'products_category_id_index')) $table->dropIndex(['category_id']);
            if ($this->hasIndex('products', 'products_seller_id_index')) $table->dropIndex(['seller_id']);
            if ($this->hasIndex('products', 'products_price_index')) $table->dropIndex(['price']);
        });

        Schema::table('orders', function (Blueprint $table) {
            if ($this->hasIndex('orders', 'orders_status_index')) $table->dropIndex(['status']);
            if ($this->hasIndex('orders', 'orders_user_id_index')) $table->dropIndex(['user_id']);
            if ($this->hasIndex('orders', 'orders_created_at_index')) $table->dropIndex(['created_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            if ($this->hasIndex('categories', 'categories_slug_index')) $table->dropIndex(['slug']);
            if ($this->hasIndex('categories', 'categories_parent_id_index')) $table->dropIndex(['parent_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            if ($this->hasIndex('reviews', 'reviews_product_id_index')) $table->dropIndex(['product_id']);
            if ($this->hasIndex('reviews', 'reviews_rating_index')) $table->dropIndex(['rating']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            if ($this->hasIndex('cart_items', 'cart_items_cart_id_index')) $table->dropIndex(['cart_id']);
            if ($this->hasIndex('cart_items', 'cart_items_product_id_index')) $table->dropIndex(['product_id']);
        });
    }

    private function hasIndex($table, $index)
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();
        $results = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
        return count($results) > 0;
    }
};
