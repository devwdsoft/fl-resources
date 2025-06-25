<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FootballNewsTag extends Model
{
    use HasFactory;

    protected $table = 'football_news_tags';

    protected $fillable = [
        'football_news_id',
        'title',
        'type',
        'href',
        'provider',
    ];

    public function news()
    {
        return $this->belongsTo(FootballNews::class, 'football_news_id');
    }
}
