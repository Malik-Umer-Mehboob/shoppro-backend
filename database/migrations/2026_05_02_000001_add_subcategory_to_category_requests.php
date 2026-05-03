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
        Schema::table('category_requests', function (Blueprint $table) {
            $table->string('subcategory_name')->nullable()->after('name');
            $table->text('subcategory_description')->nullable()->after('subcategory_name');
            $table->text('reason')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_requests', function (Blueprint $table) {
            $table->dropColumn(['subcategory_name', 'subcategory_description', 'reason']);
        });
    }
};
