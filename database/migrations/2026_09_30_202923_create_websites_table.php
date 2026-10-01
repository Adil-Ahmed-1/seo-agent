<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Basic Info
            $table->string('name');
            $table->string('url');
            $table->string('niche');
            $table->string('language', 10)->default('en');
            $table->text('business_description')->nullable();
            $table->text('target_audience')->nullable();
            
            // Platform
            $table->enum('platform', ['wordpress', 'laravel', 'static'])->default('wordpress');
            $table->string('status')->default('pending');
            
            // Credentials (encrypted JSON)
            $table->text('credentials')->nullable();
            
            // Crawled data
            $table->json('crawled_data')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['team_id', 'status']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};