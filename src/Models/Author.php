<?php

namespace NiekPH\LaravelPosts\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Resources\Json\JsonResource;
use NiekPH\LaravelPosts\Database\Factories\AuthorFactory;
use NiekPH\LaravelPosts\Http\Resources\AuthorResource;
use NiekPH\LaravelPosts\LaravelPosts;

/**
 * @property string $name
 * @property string $slug
 * @property string $email
 * @property ?string $profile_picture
 * @property ?string $description
 * @property ?string $role
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read HasMany<Post, Author> $posts
 *
 * @mixin Model
 */
class Author extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.authors', 'authors');
    }

    public function getConnectionName()
    {
        return config('posts.database.connection');
    }

    protected $fillable = [
        'name',
        'slug',
        'email',
        'profile_picture',
        'description',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(LaravelPosts::$postModel);
    }

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }

    public function toResource(?string $resourceClass = null): JsonResource
    {
        return new AuthorResource($this);
    }
}
