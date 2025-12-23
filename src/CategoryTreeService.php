<?php

namespace NiekPH\LaravelPosts;

use Illuminate\Support\Collection;
use NiekPH\LaravelPosts\Models\Category;

class CategoryTreeService
{
    /**
     * Get a category tree starting from a specific category or from root level.
     *
     * @param  Category|null  $category  The parent category to start from (null for root level)
     * @param  bool  $includePosts  Whether to include posts for each category (default: false)
     * @param  bool  $includeUnpublishedPosts  Whether to include unpublished posts (default: false)
     * @return Collection The hierarchical collection of categories with loaded child relationships
     */
    public function getCategoryTree(
        ?Category $category = null,
        bool $includePosts = false,
        bool $includeUnpublishedPosts = false,
    ): Collection {
        $categoryModel = LaravelPosts::$categoryModel;

        // Get all categories that we need for building the tree
        $allCategories = $this->getAllRelevantCategories(
            $categoryModel,
            $category,
            $includePosts,
            $includeUnpublishedPosts,
        );

        if ($category) {
            // Start from the specified category
            $rootCategories = collect([$category]);

            // Load posts for the starting category if requested
            if ($includePosts && ! $category->relationLoaded('posts')) {
                $category->load([
                    'posts' => function ($query) use ($includeUnpublishedPosts) {
                        if (! $includeUnpublishedPosts) {
                            $query->whereNotNull('published_at');
                        }
                    },
                ]);
            }

            // Attach child categories recursively
            $this->attachChildCategories($rootCategories, $allCategories, $includePosts, $includeUnpublishedPosts);

            return $rootCategories;
        }

        // Start from root categories (those without a parent)
        $rootCategories = $allCategories->whereNull('parent_category_id')->values();

        if ($includePosts) {
            $rootCategories->load([
                'posts' => function ($query) use ($includeUnpublishedPosts) {
                    if (! $includeUnpublishedPosts) {
                        $query->whereNotNull('published_at');
                    }
                },
            ]);
        }

        // Attach child categories recursively
        $this->attachChildCategories($rootCategories, $allCategories, $includePosts, $includeUnpublishedPosts);

        return $rootCategories;
    }

    /**
     * Get all categories relevant for building the tree.
     */
    protected function getAllRelevantCategories(
        string $categoryModel,
        ?Category $category,
        bool $includePosts,
        bool $includeUnpublishedPosts,
    ): Collection {
        $query = $categoryModel::query();

        if ($category) {
            // Limit to this category and all of its descendants
            $query->where(function ($q) use ($category) {
                $q->where('id', $category->id)
                    ->orWhere('full_path', 'like', $category->full_path.'/%');
            });
        }

        if ($includePosts) {
            $query->with([
                'child_categories',
                'posts' => function ($postQuery) use ($includeUnpublishedPosts) {
                    if (! $includeUnpublishedPosts) {
                        $postQuery->whereNotNull('published_at');
                    }
                },
            ]);
        } else {
            $query->with('child_categories');
        }

        return $query->get();
    }

    /**
     * Attach child categories to the given parent categories recursively.
     */
    protected function attachChildCategories(
        Collection $parents,
        Collection $allCategories,
        bool $includePosts,
        bool $includeUnpublishedPosts,
    ): void {
        $parents->each(function (Category $parent) use (
            $allCategories,
            $includePosts,
            $includeUnpublishedPosts,
        ) {
            $children = $allCategories
                ->where('parent_category_id', $parent->id)
                ->values();

            if ($includePosts && ! $children->isEmpty()) {
                $children->loadMissing([
                    'posts' => function ($query) use ($includeUnpublishedPosts) {
                        if (! $includeUnpublishedPosts) {
                            $query->whereNotNull('published_at');
                        }
                    },
                ]);
            }

            $parent->setRelation('child_categories', $children);

            if ($children->isNotEmpty()) {
                $this->attachChildCategories($children, $allCategories, $includePosts, $includeUnpublishedPosts);
            }
        });
    }
}
