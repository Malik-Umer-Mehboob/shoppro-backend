<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products indexes
        if (!Schema::hasIndex('products', 'products_status_created_at_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['status', 'created_at'],
                    'products_status_created_at_index');
                $table->index('seller_id',
                    'products_seller_id_index');
                $table->index('category_id',
                    'products_category_id_index');
                $table->index('is_featured',
                    'products_is_featured_index');
            });
        }

        // Orders indexes
        if (!Schema::hasIndex('orders', 'orders_user_id_status_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['user_id', 'status'],
                    'orders_user_id_status_index');
                $table->index('status',
                    'orders_status_index');
                $table->index('created_at',
                    'orders_created_at_index');
            });
        }

        // Notifications indexes
        if (!Schema::hasIndex('notifications',
            'notifications_notifiable_read_index')) {
            Schema::table('notifications',
                function (Blueprint $table) {
                $table->index(
                    ['notifiable_id', 'notifiable_type', 'read_at'],
                    'notifications_notifiable_read_index'
                );
            });
        }

        // Rider assignments indexes
        if (!Schema::hasIndex('rider_assignments',
            'rider_assignments_rider_status_index')) {
            Schema::table('rider_assignments',
                function (Blueprint $table) {
                $table->index(['rider_id', 'status'],
                    'rider_assignments_rider_status_index');
                $table->index('order_id',
                    'rider_assignments_order_id_index');
            });
        }

        // Tickets indexes
        if (!Schema::hasIndex('tickets',
            'tickets_status_created_index')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->index(['status', 'created_at'],
                    'tickets_status_created_index');
                $table->index('user_id',
                    'tickets_user_id_index');
            });
        }

        // Categories indexes
        if (!Schema::hasIndex('categories',
            'categories_parent_active_index')) {
            Schema::table('categories',
                function (Blueprint $table) {
                $table->index(['parent_id', 'is_active'],
                    'categories_parent_active_index');
            });
        }

        // Sessions index
        Schema::table('sessions', function (Blueprint $table) {
            try {
                $table->index('last_activity',
                    'sessions_last_activity_index');
            } catch (\Exception $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_created_at_index');
            $table->dropIndex('products_seller_id_index');
            $table->dropIndex('products_category_id_index');
            $table->dropIndex('products_is_featured_index');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_status_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_created_at_index');
        });
    }
};
