<?php

namespace NiekPH\LaravelPosts\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\Resources\Json\JsonResource;
use NiekPH\LaravelPosts\Database\Factories\AuthorFactory;
use NiekPH\LaravelPosts\Http\Resources\TagResource;
use NiekPH\LaravelPosts\LaravelPosts;

/**
 * @property string $name
 * @property string $slug
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $posts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 *
 * @mixin Model
 */
class Tag extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.tags', 'tags');
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

    public function toResource(?string $resourceClass = null): JsonResource
    {
        return new TagResource($this);
    }
}
