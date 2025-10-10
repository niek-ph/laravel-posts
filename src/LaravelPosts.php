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

    public function getCategoryTree(?Category $category = null, bool $includePosts = false): Collection
    {
        return app(CategoryTreeService::class)->getCategoryTree($category, $includePosts);
    }
}
