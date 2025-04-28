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
                $table->json('meta')->nullable();
                $table->integer('publishedAt');
                $table->json('tags')->nullable();
                $table->json('body')->nullable();
                $table->string('imageUrl', 300);
                $table->string('alt', 300);
                $table->tinyInteger('updatedTime');
                $table->timestamps();
                $table->string('imageExt', 10)->nullable();
                $table->enum('status', ['draft', 'review-request', 'publish'])->default('draft');
                $table->json('related_posts')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_news');
    }
};
