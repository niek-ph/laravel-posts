<?php

namespace NiekPH\LaravelPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use NiekPH\LaravelPosts\Database\Factories\AuthorFactory;
use NiekPH\LaravelPosts\LaravelPosts;

class Tag extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.tags');
    }

    public function getConnectionName()
    {
        return config('posts.database.connection');
    }

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get all posts that are assigned this tag.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(LaravelPosts::$postModel, 'taggable');
    }

    /**
     * Get all categories that are assigned this tag.
     */
    public function categories(): MorphToMany
    {
        return $this->morphedByMany(LaravelPosts::$categoryModel, 'taggable');
    }

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }
}
