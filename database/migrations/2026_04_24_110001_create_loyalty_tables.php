<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('threshold'); // total points required
            $table->json('benefits')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points');
            $table->string('type'); // earn, redeem
            $table->string('description');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('points_required');
            $table->string('reward_type'); // discount_percent, discount_fixed, free_shipping
            $table->decimal('reward_value', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('total_loyalty_points')->default(0);
            $table->foreignId('loyalty_tier_id')->nullable()->constrained('loyalty_tiers')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['loyalty_tier_id']);
            $table->dropColumn(['total_loyalty_points', 'loyalty_tier_id']);
        });
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('loyalty_tiers');
    }
};
