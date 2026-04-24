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
        // Add JSON translations column to content tables
        Schema::table('products', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('description');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('description');
        });

        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('content');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('content');
        });

        // Add language_id to user-generated content and support
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->constrained()->onDelete('set null');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->constrained()->onDelete('set null');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->constrained()->onDelete('set null');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->constrained()->onDelete('set null');
        });
        
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->constrained()->onDelete('set null');
        });
        
        Schema::table('newsletters', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('translations');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('translations');
        });

        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            $table->dropColumn('translations');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('translations');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropColumn('language_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropColumn('language_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropColumn('language_id');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropColumn('language_id');
        });
        
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropColumn('language_id');
        });
        
        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropColumn('language_id');
        });
    }
};
