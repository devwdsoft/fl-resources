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
        if (!Schema::hasTable('football_news')) {
            Schema::create('football_news', function (Blueprint $table) {
                $table->id();
                $table->string('title', 300);
                $table->string('slug', 300);
                $table->integer('publishedAt');
                $table->json('body')->nullable();
                $table->string('imageUrl', 300);
                $table->string('alt', 300);
                $table->tinyInteger('updatedTime');
                $table->string('imageExt', 10)->nullable();
                $table->enum('status', ['draft', 'review-request', 'publish'])->default('draft');
                $table->json('related_posts')->nullable();
                $table->timestamps();
            });

            // Bảng phụ: football_news_tags
            Schema::create('football_news_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('football_news_id')->constrained('football_news')->onDelete('cascade');
                $table->string('title');
                $table->string('type')->nullable();
                $table->string('href')->nullable();
                $table->string('provider')->nullable();
                $table->timestamps();
            });

            // Bảng phụ: football_news_meta_tags (SEO meta tags dạng foreach)
            Schema::create('football_news_meta_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('football_news_id')->constrained('football_news')->onDelete('cascade');
                $table->enum('tag_type', ['name', 'property']);
                $table->string('tag_key');
                $table->text('tag_value');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_news_meta_tags');
        Schema::dropIfExists('football_news_tags');
        Schema::dropIfExists('football_news');
    }
};
