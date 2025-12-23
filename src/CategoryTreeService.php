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
                    'posts' => function ($query) use ($includeUnpublishedPosts): void {
                        if (! $includeUnpublishedPosts) {
                            $query->whereNotNull('published_at');
                        }

                        // Ensure posts are always ordered consistently
                        $query->orderBy('published_at')
                            ->orderBy('title');
                    },
                ]);
            }

            // Attach child categories recursively (children will already be sorted)
            $this->attachChildCategories(
                $rootCategories,
                $allCategories,
                $includePosts,
                $includeUnpublishedPosts
            );

            return $rootCategories;
        }

        // Start from root categories (those without a parent)
        /** @var Collection<Category> $rootCategories */
        $rootCategories = $allCategories
            ->whereNull('parent_category_id')
            ->values();

        if ($includePosts) {
            $rootCategories->each(fn (Category $category) => $category->load([
                'posts' => function ($query) use ($includeUnpublishedPosts): void {
                    if (! $includeUnpublishedPosts) {
                        $query->whereNotNull('published_at');
                    }

                    // Ensure posts are always ordered consistently
                    $query->orderBy('published_at')
                        ->orderBy('title');
                },
            ]));
        }

        // Attach child categories recursively (children will already be sorted)
        $this->attachChildCategories(
            $rootCategories,
            $allCategories,
            $includePosts,
            $includeUnpublishedPosts
        );

        return $rootCategories;
    }

    /**
     * Get all categories relevant for building the tree.
     *
     * Categories are always ordered by sort_order first and then by name.
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
            $query->where(function ($q) use ($category): void {
                $q->where('id', $category->id)
                    ->orWhere('full_path', 'like', $category->full_path.'/%');
            });
        }

        if ($includePosts) {
            $query->with([
                'child_categories',
                'posts' => function ($postQuery) use ($includeUnpublishedPosts): void {
                    if (! $includeUnpublishedPosts) {
                        $postQuery->whereNotNull('published_at');
                    }

                    // Ensure posts are always ordered consistently
                    $postQuery->orderBy('published_at')
                        ->orderBy('title');
                },
            ]);
        } else {
            $query->with('child_categories');
        }

        // Ensure categories are always ordered consistently
        $query->orderBy('sort_order')
            ->orderBy('name');

        return $query->get();
    }

    /**
     * Attach child categories to each category in the provided collection, recursively.
     *
     * Child categories are always ordered by sort_order first and then by name.
     */
    protected function attachChildCategories(
        Collection $parentCategories,
        Collection $allCategories,
        bool $includePosts,
        bool $includeUnpublishedPosts,
    ): void {
        $parentCategories->each(function (Category $parent) use (
            $allCategories,
            $includePosts,
            $includeUnpublishedPosts
        ): void {
            // Get and sort children for this parent
            $children = $allCategories
                ->where('parent_category_id', $parent->id)
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['name', 'asc'],
                ])
                ->values();

            if ($children->isEmpty()) {
                // Ensure the relation is set even if there are no children
                $parent->setRelation('child_categories', collect());

                return;
            }

            if ($includePosts) {
                $children->each(fn (Category $child) => $child->load([
                    'posts' => function ($query) use ($includeUnpublishedPosts): void {
                        if (! $includeUnpublishedPosts) {
                            $query->whereNotNull('published_at');
                        }

                        // Ensure posts are always ordered consistently
                        $query->orderBy('published_at')
                            ->orderBy('title');
                    },
                ]));
            }

            // Set the sorted children on the parent
            $parent->setRelation('child_categories', $children);

            // Recurse into children
            $this->attachChildCategories(
                $children,
                $allCategories,
                $includePosts,
                $includeUnpublishedPosts
            );
        });
    }
}
