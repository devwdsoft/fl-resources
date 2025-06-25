<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FootballNewsTagRelation extends Model
{
    use HasFactory;

    protected $table = 'football_news_tag_relations';

    protected $fillable = [
        'football_news_id',
        'tag_id',
    ];

    public function news()
    {
        return $this->belongsTo(FootballNews::class);
    }

    public function tag()
    {
        return $this->belongsTo(FootballNewsTag::class);
    }
}
