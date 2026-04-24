<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('billed_to')->nullable();
            $table->json('shipped_to')->nullable();
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
        });

        // Update orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->string('refund_reason')->nullable();
        });

        // Create order_line_items table
        Schema::create('order_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_line_items');
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
            $table->dropColumn('refund_reason');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['billed_to', 'shipped_to', 'sub_total', 'shipping_cost', 'tax', 'discount']);
        });
    }
};
