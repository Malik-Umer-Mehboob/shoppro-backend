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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index(); // e.g., 'messages.welcome'
            $table->text('text');
            $table->foreignId('language_id')->constrained()->onDelete('cascade');
            $table->string('group')->default('messages'); // e.g., 'messages', 'validation', 'email'
            $table->timestamps();
            
            $table->unique(['key', 'language_id', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
