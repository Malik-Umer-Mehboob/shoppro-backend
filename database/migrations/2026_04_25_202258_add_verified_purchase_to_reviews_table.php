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
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'verified_purchase')) {
                $table->boolean('verified_purchase')->default(false)->after('comment');
            }
            if (!Schema::hasColumn('reviews', 'is_approved')) {
                $table->boolean('is_approved')->default(false)->after('verified_purchase');
            }
            if (!Schema::hasColumn('reviews', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('is_approved');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['verified_purchase', 'is_approved', 'approved_at']);
        });
    }
};
