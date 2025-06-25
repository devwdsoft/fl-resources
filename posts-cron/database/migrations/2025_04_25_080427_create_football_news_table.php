<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('football_news', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title', 300);
            $table->string('slug', 300);
            $table->string('description', 500)->nullable();
            $table->integer('publishedAt');
            $table->string('imageUrl', 300);
            $table->string('alt', 300)->nullable();
            $table->tinyInteger('updatedTime')->default(0);
            $table->timestamps();
            $table->string('imageExt', 10)->nullable();
            $table->enum('status', ['draft', 'review-request', 'publish'])->default('draft');
            $table->json('related_posts')->nullable();
            $table->boolean('meta_added')->default(false);
        });

        Schema::create('football_news_tags', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('football_news_tag_relations', function (Blueprint $table) {
            $table->id();
            $table->string('football_news_id');
            $table->unsignedBigInteger('football_news_tag_id');

            $table->foreign('football_news_id')->references('id')->on('football_news')->onDelete('cascade');
            $table->foreign('football_news_tag_id')->references('id')->on('football_news_tags')->onDelete('cascade');
        });

        Schema::create('football_news_meta_tags', function (Blueprint $table) {
            $table->id();
            $table->string('football_news_id');
            $table->enum('tag_type', ['name', 'property']);
            $table->string('tag_key');
            $table->text('tag_value')->nullable();
            $table->timestamps();

            $table->foreign('football_news_id')->references('id')->on('football_news')->onDelete('cascade');
        });

        Schema::create('football_news_body_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('football_news_id');
            $table->unsignedInteger('offset');
            $table->string('type', 50);
            $table->string('content_type', 50)->nullable();
            $table->longText('content')->nullable();

            $table->foreign('football_news_id')
                ->references('id')->on('football_news')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_news_meta_tags');
        Schema::dropIfExists('football_news_tag_relations');
        Schema::dropIfExists('football_news_tags');
        Schema::dropIfExists('football_news');
        Schema::dropIfExists('football_news_body_blocks');
    }
};
