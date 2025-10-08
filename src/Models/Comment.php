<?php

namespace NiekPH\LaravelPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NiekPH\LaravelPosts\Database\Factories\CommentFactory;
use NiekPH\LaravelPosts\LaravelPosts;

class Comment extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.comments');
    }

    public function getConnectionName()
    {
        return config('posts.database.connection');
    }

    protected $fillable = [
        'stars',
        'comment',
        'post_id',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(LaravelPosts::$postModel);
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
