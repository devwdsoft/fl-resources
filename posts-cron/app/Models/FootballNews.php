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
        'description',
        'publishedAt',
        'imageUrl',
        'alt',
        'updatedTime',
        'imageExt',
        'status',
        'related_posts',
        'has_meta',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    // Tags (many-to-many)
    public function tags()
    {
        return $this->belongsToMany(
            FootballNewsTag::class,
            'football_news_tag_relations',
            'football_news_id',
            'football_news_tag_id'
        );
    }

    // Meta tags (one-to-many)
    public function metaTags()
    {
        return $this->hasMany(FootballNewsMetaTag::class, 'football_news_id');
    }

    // Body blocks (one-to-many)
    public function bodyBlocks()
    {
        return $this->hasMany(FootballNewsBodyBlock::class, 'football_news_id');
    }
}
