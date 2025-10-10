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
     * @return Collection The hierarchical collection of categories with loaded child relationships
     */
    public function getCategoryTree(?Category $category = null, bool $includePosts = false): Collection
    {
        $categoryModel = LaravelPosts::$categoryModel;

        // Get all categories that we need for building the tree
        $allCategories = $this->getAllRelevantCategories($categoryModel, $category, $includePosts);

        if ($category) {
            // Start from the specified category
            $rootCategories = collect([$category]);

            // Load posts for the starting category if requested
            if ($includePosts && !$category->relationLoaded('posts')) {
                $category->load('posts');
            }
        } else {
            // Start from root level categories
            $rootCategories = $allCategories->whereNull('parent_category_id');
        }

        // Build the hierarchical structure
        $this->buildCategoryHierarchy($allCategories, $rootCategories, $includePosts);

        return $rootCategories->values();
    }

    /**
     * Get all categories needed to build the tree.
     */
    private function getAllRelevantCategories($categoryModel, ?Category $category, bool $includePosts): Collection
    {
        $query = $categoryModel::query();

        if ($category) {
            // Get all descendants of the specified category
            $query->where('full_path', 'like', $category->full_path . '/%')
                ->orWhere('parent_category_id', $category->id);
        }
        // If no specific category, we'll get all categories later

        // Add posts relationship if requested
        if ($includePosts) {
            $query->with('posts');
        }

        // Get categories ordered by sort_order and name
        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        // If no specific category was provided, get all categories
        if (!$category) {
            $categories = $categoryModel::query()
                ->when($includePosts, fn($q) => $q->with('posts'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return $categories;
    }

    /**
     * Recursively build the category hierarchy by loading child relationships.
     *
     * @param  Collection  $allCategories  All categories that were fetched
     * @param  Collection  $parentCategories  Categories at the current level
     * @param  bool  $includePosts  Whether posts are included in the tree
     */
    private function buildCategoryHierarchy(
        Collection $allCategories,
        Collection $parentCategories,
        bool $includePosts
    ): void {
        foreach ($parentCategories as $parentCategory) {
            // Find all direct children of this category
            $children = $allCategories->where('parent_category_id', $parentCategory->id)->values();

            // Set the relationship to avoid additional queries
            $parentCategory->setRelation('child_categories', $children);

            // If posts weren't eager loaded but we need them, load them now
            if ($includePosts && !$parentCategory->relationLoaded('posts')) {
                $parentCategory->load('posts');
            }

            // Recursively build hierarchy for children if they exist
            if ($children->isNotEmpty()) {
                $this->buildCategoryHierarchy($allCategories, $children, $includePosts);
            }
        }
    }
}
