<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FootballNewsBodyBlock extends Model
{
    use HasFactory;

    protected $table = 'football_news_body_blocks';

    protected $fillable = [
        'football_news_id',
        'offset',
        'type',
        'content_type',
        'content',
    ];

    public $timestamps = false;

    public function news()
    {
        return $this->belongsTo(FootballNews::class, 'football_news_id');
    }
}
