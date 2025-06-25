<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FootballNewsTag extends Model
{
    use HasFactory;

    protected $table = 'football_news_tags';

    protected $fillable = [
        'title',
        'type',
    ];

    public function news()
    {
        return $this->belongsToMany(FootballNews::class, 'football_news_tag_relations');
    }
}
