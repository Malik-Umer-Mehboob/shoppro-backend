<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change enum values and update existing 'Pending' records
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open'");
        DB::table('tickets')->where('status', 'Pending')->update(['status' => 'In Progress']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('Open', 'Pending', 'Resolved', 'Closed') DEFAULT 'Open'");
        DB::table('tickets')->where('status', 'In Progress')->update(['status' => 'Pending']);
    }
};
