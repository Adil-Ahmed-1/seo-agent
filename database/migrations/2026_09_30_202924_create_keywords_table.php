<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            
            // Keyword data
            $table->string('keyword');
            $table->integer('search_volume')->nullable();
            $table->integer('difficulty')->nullable(); // 0-100
            $table->decimal('cpc', 8, 2)->nullable();
            $table->string('intent')->nullable(); // informational, commercial, transactional, navigational
            
            // Ranking
            $table->integer('current_rank')->nullable();
            $table->integer('target_rank')->default(10);
            $table->timestamp('last_checked_at')->nullable();
            
            // Status
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            
            $table->timestamps();
            
            $table->index(['website_id', 'status']);
            $table->index('keyword');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};