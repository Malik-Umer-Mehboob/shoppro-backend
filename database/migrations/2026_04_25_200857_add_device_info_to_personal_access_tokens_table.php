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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_access_tokens', 'device_name')) {
                $table->string('device_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('personal_access_tokens', 'device_type')) {
                $table->string('device_type')->nullable()->after('device_name');
            }
            if (!Schema::hasColumn('personal_access_tokens', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('personal_access_tokens', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'device_type', 'ip_address', 'user_agent']);
        });
    }
};
