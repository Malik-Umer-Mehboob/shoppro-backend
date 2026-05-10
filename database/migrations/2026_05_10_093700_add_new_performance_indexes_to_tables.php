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
            $table->index('category_id');
            $table->index('price');
            $table->index('created_at');
            // Assuming status is used for is_active
            if (Schema::hasColumn('products', 'status')) {
                $table->index('status');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id');
            if (Schema::hasColumn('categories', 'is_active')) {
                $table->index('is_active');
            }
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['price']);
            $table->dropIndex(['created_at']);
            if (Schema::hasColumn('products', 'status')) {
                $table->dropIndex(['status']);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            if (Schema::hasColumn('categories', 'is_active')) {
                $table->dropIndex(['is_active']);
            }
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
