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
            if (!Schema::hasColumn('users', 'vehicle_type')) {
                $table->string('vehicle_type')->nullable()->after('mobile_number');
            }
            if (!Schema::hasColumn('users', 'cnic')) {
                $table->string('cnic')->nullable()->after('vehicle_type');
            }
            if (!Schema::hasColumn('users', 'delivery_zone')) {
                $table->string('delivery_zone')->nullable()->after('cnic');
            }
            if (!Schema::hasColumn('users', 'staff_status')) {
                $table->string('staff_status')->default('active')->after('delivery_zone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type', 'cnic', 'delivery_zone', 'staff_status']);
        });
    }
};
