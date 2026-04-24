<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('wishlists', function (Blueprint $table) {
            if (!Schema::hasColumn('wishlists', 'privacy')) {
                $table->enum('privacy', ['public', 'private'])->default('private')->after('user_id');
            }
            if (!Schema::hasColumn('wishlists', 'share_token')) {
                $table->string('share_token')->nullable()->unique()->after('privacy');
            }
        });
    }

    public function down()
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn(['privacy', 'share_token']);
        });
    }
};
