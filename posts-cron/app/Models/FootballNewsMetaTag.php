<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FootballNewsMetaTag extends Model
{
    use HasFactory;

    protected $table = 'football_news_meta_tags';

    protected $fillable = [
        'football_news_id',
        'tag_type',   // name | property
        'tag_key',    // ví dụ: description, og:title
        'tag_value',  // nội dung
    ];

    public function news()
    {
        return $this->belongsTo(FootballNews::class, 'football_news_id');
    }
}
