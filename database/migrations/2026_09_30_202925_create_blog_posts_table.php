<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            
            // Content
            $table->string('title');
            $table->string('slug');
            $table->longText('content_html');
            $table->text('meta_description');
            $table->string('focus_keyword');
            $table->json('secondary_keywords')->nullable();
            
            // Media
            $table->string('featured_image')->nullable();
            $table->json('images')->nullable();
            
            // SEO
            $table->json('seo_analysis')->nullable();
            $table->integer('seo_score')->default(0);
            $table->integer('word_count')->default(0);
            
            // Status Workflow
            $table->enum('status', [
                'generating',
                'draft',
                'pending_review',
                'approved',
                'scheduled',
                'publishing',
                'published',
                'failed',
            ])->default('draft');
            
            // Publishing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('remote_id')->nullable();
            $table->string('remote_url')->nullable();
            $table->text('error_message')->nullable();
            
            // AI metadata
            $table->string('ai_model')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 8, 4)->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['website_id', 'status']);
            $table->index('status');
            $table->unique(['website_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};