<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FootballNews extends Model
{
    use HasFactory;

    protected $table = 'football_news';

    protected $fillable = [
        'id',
        'title',
        'slug',
        'publishedAt',
        'body',
        'imageUrl',
        'alt',
        'updatedTime',
        'imageExt',
        'status',
        'related_posts'
    ];

    protected $casts = [
        'body' => 'array',
        'related_posts' => 'array',
    ];

    /**
     * Tags liên quan đến bài viết
     */
    public function tags()
    {
        return $this->hasMany(FootballNewsTag::class);
    }

    /**
     * Meta tags dạng name/property để render SEO
     */
    public function metaTags()
    {
        return $this->hasMany(FootballNewsMetaTag::class);
    }
}
