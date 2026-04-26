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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cod', 'bank_transfer', 'stripe'])
                ->default('cod')->change();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])
                ->default('pending')->change();
            $table->text('payment_notes')->nullable()->after('payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_notes');
        });
    }
};
