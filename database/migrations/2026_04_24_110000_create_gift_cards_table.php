<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('initial_amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->foreignId('buyer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email');
            $table->text('message')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gift_cards');
    }
};
