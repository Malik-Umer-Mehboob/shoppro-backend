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
        // User Segments
        Schema::create('user_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('rule_set'); // Logic for fetching users
            $table->timestamps();
        });

        // Email Templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('subject');
            $table->longText('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Email Campaigns
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->string('ab_test_subject')->nullable();
            $table->longText('content');
            $table->foreignId('segment_id')->nullable()->constrained('user_segments')->onDelete('set null');
            $table->datetime('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled'])->default('draft');
            $table->json('results')->nullable(); // Store stats like total_sent, total_opens
            $table->timestamps();
        });

        // Email Campaign Analytics
        Schema::create('email_campaign_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->constrained('email_campaigns')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->datetime('opened_at')->nullable();
            $table->datetime('clicked_at')->nullable();
            $table->datetime('converted_at')->nullable();
            $table->timestamps();
        });

        // Newsletters
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('content');
            $table->datetime('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletters');
        Schema::dropIfExists('email_campaign_analytics');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('user_segments');
    }
};
