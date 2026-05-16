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
        Schema::table('users', function (Blueprint $table) {
            $table->string('store_name')->nullable()->after('name');
            $table->text('store_description')->nullable()->after('store_name');
            $table->string('business_type')->nullable()->after('store_description');
            $table->string('store_logo')->nullable()->after('business_type');
            $table->enum('seller_status', ['pending', 'approved', 'rejected', 'suspended'])
                  ->default('pending')
                  ->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'store_name',
                'store_description',
                'business_type',
                'store_logo',
                'seller_status'
            ]);
        });
    }
};
