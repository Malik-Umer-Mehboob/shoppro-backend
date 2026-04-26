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
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->enum('reason', ['defective', 'wrong_item', 'not_as_described', 'changed_mind', 'damaged_in_shipping', 'other']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'picked_up', 'refunded'])->default('pending');
            $table->enum('refund_type', ['full_refund', 'partial_refund', 'store_credit', 'replacement'])->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
