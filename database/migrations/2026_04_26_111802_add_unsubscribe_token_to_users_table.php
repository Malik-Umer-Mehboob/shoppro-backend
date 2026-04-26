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
            $table->string('unsubscribe_token')->nullable()->unique()->after('is_blocked');
        });

        \App\Models\User::whereNull('unsubscribe_token')->each(function ($user) {
            $user->update([
                'unsubscribe_token' => \Illuminate\Support\Str::random(64)
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('unsubscribe_token');
        });
    }
};
