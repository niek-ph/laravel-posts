<?php

namespace NiekPH\LaravelPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
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
        'subtitle',
        'slug',
        'full_path',
        'sort_order',
        'body',
        'published_at',
        'metadata',
        'featured_image',
        'seo_title',
        'seo_description',
        'author_id',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }
            $post->updateFullPath();
        });

        static::updating(function (Post $post) {
            if ($post->isDirty(['slug', 'category_id'])) {
                $post->updateFullPath();
            }
        });
    }

    /**
     * Generate a unique slug within the post's category
     */
    public function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        // Check for uniqueness within the category
        while ($this->slugExistsInCategory($slug)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists in the same category
     */
    protected function slugExistsInCategory(string $slug): bool
    {
        $query = static::where('slug', $slug)
            ->where('category_id', $this->category_id);

        // Exclude current post when updating
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Update the full path based on category and post slug
     */
    public function updateFullPath(): void
    {
        if ($this->category_id && $this->category) {
            $this->full_path = $this->category->full_path.'/'.$this->slug;
        } else {
            // Posts without category go to root
            $this->full_path = $this->slug;
        }
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

    /**
     * Scope for published posts
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
