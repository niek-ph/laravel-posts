<?php

namespace NiekPH\LaravelPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use NiekPH\LaravelPosts\Database\Factories\PostFactory;
use NiekPH\LaravelPosts\LaravelPosts;

class Post extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.posts');
    }

    public function getConnectionName()
    {
        return config('posts.database.connection');
    }

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'is_published',
        'metadata',
        'body',
        'cover_image',
        'image_thumbnail',
        'author_id',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(LaravelPosts::$authorModel);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LaravelPosts::$categoryModel);
    }

    public function comments(): BelongsToMany
    {
        return $this->belongsToMany(LaravelPosts::$commentModel, config('posts.database.tables.post_comments'));
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
