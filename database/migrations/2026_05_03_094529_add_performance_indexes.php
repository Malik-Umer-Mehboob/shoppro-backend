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
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index('seller_id');
            $table->index('category_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'read_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('user_id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['seller_id']);
            $table->dropIndex(['category_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['status']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_id', 'read_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['last_activity']);
        });
    }
};
