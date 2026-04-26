<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'max_uses')) {
                $table->unsignedInteger('max_uses')
                    ->nullable()
                    ->after('minimum_order_amount')
                    ->comment('Total times this coupon can be used across all users. NULL = unlimited');
            }
            if (!Schema::hasColumn('coupons', 'per_user_limit')) {
                $table->unsignedInteger('per_user_limit')
                    ->default(1)
                    ->after('max_uses')
                    ->comment('Max times one user can use this coupon');
            }
            if (!Schema::hasColumn('coupons', 'used_count')) {
                $table->unsignedInteger('used_count')
                    ->default(0)
                    ->after('per_user_limit')
                    ->comment('Total times this coupon has been used so far');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('coupons', 'max_uses')) $columns[] = 'max_uses';
            if (Schema::hasColumn('coupons', 'per_user_limit')) $columns[] = 'per_user_limit';
            if (Schema::hasColumn('coupons', 'used_count')) $columns[] = 'used_count';
            if (!empty($columns)) $table->dropColumn($columns);
        });
    }
};
