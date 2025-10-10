<?php

namespace NiekPH\LaravelPosts;

use Illuminate\Support\Collection;
use NiekPH\LaravelPosts\Models\Author;
use NiekPH\LaravelPosts\Models\Category;
use NiekPH\LaravelPosts\Models\Comment;
use NiekPH\LaravelPosts\Models\Post;
use NiekPH\LaravelPosts\Models\Tag;

class LaravelPosts
{
    /**
     * The author model class name.
     */
    public static string $authorModel = Author::class;

    /**
     * The post model class name.
     */
    public static string $postModel = Post::class;

    /**
     * The category model class name.
     */
    public static string $categoryModel = Category::class;

    /**
     * The comment model class name.
     */
    public static string $commentModel = Comment::class;

    /**
     * The tag model class name.
     */
    public static string $tagModel = Tag::class;

    /**
     * Set the author model class name.
     */
    public static function useAuthorModel(string $authorModel): void
    {
        static::$authorModel = $authorModel;
    }

    /**
     * Set the post model class name.
     */
    public static function usePostModel(string $postModel): void
    {
        static::$postModel = $postModel;
    }

    /**
     * Set the category model class name.
     */
    public static function useCategoryModel(string $categoryModel): void
    {
        static::$categoryModel = $categoryModel;
    }

    /**
     * Set the comment model class name.
     */
    public static function useCommentModel(string $commentModel): void
    {
        static::$commentModel = $commentModel;
    }

    /**
     * Set the tag model class name.
     */
    public static function useTagModel(string $tagModel): void
    {
        static::$tagModel = $tagModel;
    }

    /**
     * Get a category tree starting from a specific category or from root level.
     *
     * @param  Category|null  $category  The parent category to start from (null for root level)
     * @param  bool  $includePosts  Whether to include posts for each category (default: false)
     * @return Collection The hierarchical collection of categories with loaded child relationships
     */
    public static function getCategoryTree(?Category $category = null, bool $includePosts = false): Collection
    {
        $categoryModel = static::$categoryModel;

        // Build the base query with eager loading
        $query = $categoryModel::query();

        // Add posts relationship if requested
        if ($includePosts) {
            $query->with('posts');
        }

        if ($category) {
            // Start from the specified category's children
            $query->where('parent_category_id', $category->id);

            // Load posts for the starting category if requested
            if ($includePosts && ! $category->relationLoaded('posts')) {
                $category->load('posts');
            }
        } else {
            // Start from root level categories
            $query->whereNull('parent_category_id');
        }

        // Get all relevant categories in a single query for efficiency
        $allCategories = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // If we have a starting category, include it in the result
        if ($category) {
            $rootCategories = collect([$category]);
            $category->setRelation('childCategories', $allCategories->where('parent_category_id', $category->id));
        } else {
            $rootCategories = $allCategories->whereNull('parent_category_id');
        }

        // Build the hierarchical structure
        self::buildCategoryHierarchy($allCategories, $rootCategories, $includePosts);

        return $rootCategories;
    }

    /**
     * Recursively build the category hierarchy by loading child relationships.
     *
     * @param  Collection  $allCategories  All categories that were fetched
     * @param  Collection  $parentCategories  Categories at the current level
     * @param  bool  $includePosts  Whether posts are included in the tree
     */
    private static function buildCategoryHierarchy(
        Collection $allCategories,
        Collection $parentCategories,
        bool $includePosts
    ): void {
        foreach ($parentCategories as $parentCategory) {
            // Find all direct children of this category
            $children = $allCategories->where('parent_category_id', $parentCategory->id);

            // Set the relationship to avoid additional queries
            $parentCategory->setRelation('childCategories', $children);

            // If posts weren't eager loaded but we need them, load them now
            if ($includePosts && ! $parentCategory->relationLoaded('posts')) {
                $parentCategory->load('posts');
            }

            // Recursively build hierarchy for children if they exist
            if ($children->isNotEmpty()) {
                self::buildCategoryHierarchy($allCategories, $children, $includePosts);
            }
        }
    }
}
