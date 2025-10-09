<?php

namespace NiekPH\LaravelPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NiekPH\LaravelPosts\Database\Factories\CategoryFactory;
use NiekPH\LaravelPosts\LaravelPosts;

class Category extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.categories');
    }

    public function getConnectionName()
    {
        return config('posts.database.connection');
    }

    protected $fillable = [
        'name',
        'slug',
        'metadata',
        'description',
        'parent_category_id',
        'sort_order',
        'full_path'
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(LaravelPosts::$postModel);
    }

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(LaravelPosts::$categoryModel, 'parent_category_id');
    }

    public function childCategories(): HasMany
    {
        return $this->hasMany(LaravelPosts::$categoryModel, 'parent_category_id');
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
