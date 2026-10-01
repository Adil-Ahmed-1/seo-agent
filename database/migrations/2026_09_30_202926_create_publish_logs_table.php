<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publish_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_post_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Action details
            $table->string('action'); // publish, update, delete, test_connection
            $table->string('status'); // success, failed
            $table->string('platform'); // wordpress, laravel, static
            
            // Response data
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            
            // Timing
            $table->integer('duration_ms')->nullable();
            
            $table->timestamps();
            
            $table->index(['website_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_logs');
    }
};