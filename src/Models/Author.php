<?php

namespace NiekPH\LaravelPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NiekPH\LaravelPosts\Database\Factories\AuthorFactory;
use NiekPH\LaravelPosts\LaravelPosts;

class Author extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.authors');
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
}
