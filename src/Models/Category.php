<?php

namespace NiekPH\LaravelPosts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
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
        'full_path',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }


    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
            $category->updateFullPath();
        });

        static::updating(function (Category $category) {
            if ($category->isDirty(['slug', 'parent_category_id'])) {
                $category->updateFullPath();
            }
        });

        static::updated(function (Category $category) {
            // If slug or parent changed, update all descendants
            if ($category->wasChanged(['slug', 'parent_category_id'])) {
                $category->updateDescendantPaths();
                // Also update posts in the current category
                $category->updatePostPaths();
            }
        });

        static::deleting(function (Category $category) {
            // Prevent deletion if category has children (optional business rule)
            if ($category->childCategories()->count() > 0) {
                throw new \Exception('Cannot delete category with subcategories. Move or delete subcategories first.');
            }
        });
    }

    /**
     * Update the full path based on parent hierarchy
     */
    public function updateFullPath(): void
    {
        if ($this->parent_category_id) {
            // Always fetch fresh parent data to avoid stale relationship cache
            $parent = static::find($this->parent_category_id);

            if ($parent) {
                $this->full_path = $parent->full_path . '/' . $this->slug;
            } else {
                $this->full_path = $this->slug;
            }
        } else {
            $this->full_path = $this->slug;
        }
    }

    /**
     * Update paths for all descendant categories and their posts
     */
    public function updateDescendantPaths(): void
    {
        $children = $this->childCategories()->get();

        foreach ($children as $child) {
            $child->updateFullPath();
            $child->saveQuietly();

            // Recursively update their children
            $child->updateDescendantPaths();

            // Update posts in this child category
            $child->updatePostPaths();
        }
    }

    /**
     * Update full paths for all posts in this category
     */
    public function updatePostPaths(): void
    {
        $this->posts()->get()->each(function ($post) {
            $post->updateFullPath();
            $post->saveQuietly();
        });
    }

    /**
     * Generate a unique slug within the same parent category
     */
    public function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Check for uniqueness within the same parent
        while ($this->slugExistsInSameParent($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;

            // Prevent infinite loops
            if ($counter > 100) {
                $slug = $baseSlug . '-' . Str::random(4);
                break;
            }
        }

        return $slug;
    }

    /**
     * Check if slug exists under the same parent category
     */
    protected function slugExistsInSameParent(string $slug): bool
    {
        $query = static::where('slug', $slug)
            ->where('parent_category_id', $this->parent_category_id);

        // Exclude current category when updating
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
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
