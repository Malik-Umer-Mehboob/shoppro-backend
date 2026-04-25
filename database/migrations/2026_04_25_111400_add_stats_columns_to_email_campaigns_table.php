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
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->integer('sent_count')->default(0)->after('status');
            $table->integer('open_count')->default(0)->after('sent_count');
            $table->integer('click_count')->default(0)->after('open_count');
            $table->decimal('revenue', 10, 2)->default(0)->after('click_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropColumn(['sent_count', 'open_count', 'click_count', 'revenue']);
        });
    }
};
