<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('football_news', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('title', 300);
            $table->string('slug', 300);
            $table->integer('publishedAt');
            $table->json('body')->nullable();
            $table->string('imageUrl', 300);
            $table->string('alt', 300)->nullable();
            $table->tinyInteger('updatedTime')->default(0);
            $table->timestamps();
            $table->string('imageExt', 10)->nullable();
            $table->enum('status', ['draft', 'review-request', 'publish'])->default('draft');
            $table->json('related_posts')->nullable();
        });

        Schema::create('football_news_tags', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('football_news_tag_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('football_news_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->foreign('football_news_id')->references('id')->on('football_news')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('football_news_tags')->onDelete('cascade');
        });

        Schema::create('football_news_meta_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('football_news_id');
            $table->enum('tag_type', ['name', 'property']);
            $table->string('tag_key');
            $table->text('tag_value')->nullable();
            $table->timestamps();

            $table->foreign('football_news_id')->references('id')->on('football_news')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_news_meta_tags');
        Schema::dropIfExists('football_news_tag_relations');
        Schema::dropIfExists('football_news_tags');
        Schema::dropIfExists('football_news');
    }
};
