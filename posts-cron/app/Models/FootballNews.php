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
        'meta',
        'publishedAt',
        'tags',
        'body',
        'imageUrl',
        'alt',
        'updatedTime',
        'createdAt',
        'imageExt',
        'status',
        'related_posts'
    ];

    protected $casts = [
        'tags' => 'array',
        'meta' => 'object',
        "body" => 'array',
        'related_posts' => 'array'
    ];
}
