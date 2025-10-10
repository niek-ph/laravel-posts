<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use NiekPH\LaravelPosts\Facades\LaravelPosts;
use NiekPH\LaravelPosts\Models\Author;
use NiekPH\LaravelPosts\Models\Category;
use NiekPH\LaravelPosts\Models\Post;

uses(RefreshDatabase::class);

it('handles complex nested structure changes', function () {
    $author = Author::factory()->create();

    // Create nested categories
    $tech = Category::factory()->create([
        'name' => 'Tech',
    ]);
    $programming = Category::factory()->create([
        'name' => 'Programming',
        'parent_category_id' => $tech->id,
    ]);
    $webDev = Category::factory()->create([
        'name' => 'Web Development',
        'parent_category_id' => $programming->id,
    ]);
    $frameworks = Category::factory()->create([
        'name' => 'PHP Frameworks',
        'parent_category_id' => $webDev->id,
    ]);

    // Create posts at each level
    $posts = [
        Post::factory()->create([
            'title' => 'Tech Overview',
            'category_id' => $tech->id,
            'author_id' => $author->id,
        ]),
        Post::factory()->create([
            'title' => 'Programming Basics',
            'category_id' => $programming->id,
            'author_id' => $author->id,
        ]),
        Post::factory()->create([
            'title' => 'Web Trends',
            'category_id' => $webDev->id,
            'author_id' => $author->id,
        ]),
        Post::factory()->create([
            'title' => 'Laravel Tips',
            'category_id' => $frameworks->id,
            'author_id' => $author->id,
        ]),
    ];

    // Change root category slug
    $tech->update(['slug' => 'tech']);

    // Verify all paths updated
    expect($posts[0]->fresh()->full_path)->toBe('tech/tech-overview');
    expect($posts[1]->fresh()->full_path)->toBe('tech/programming/programming-basics');
    expect($posts[2]->fresh()->full_path)->toBe('tech/programming/web-development/web-trends');
    expect($posts[3]->fresh()->full_path)->toBe('tech/programming/web-development/php-frameworks/laravel-tips');
});

it('moves entire subtree to different parent', function () {
    $author = Author::factory()->create();

    $tech = Category::factory()->create(['name' => 'Technology']);
    $business = Category::factory()->create(['name' => 'Business']);

    $programming = Category::factory()->create([
        'name' => 'Programming',
        'parent_category_id' => $tech->id,
    ]);
    $webDev = Category::factory()->create([
        'name' => 'Web Development',
        'parent_category_id' => $programming->id,
    ]);

    $post = Post::factory()->create([
        'title' => 'Web Tutorial',
        'category_id' => $webDev->id,
        'author_id' => $author->id,
    ]);

    // Move programming under business
    $programming->forceFill(['parent_category_id' => $business->id])->save();

    expect($programming->fresh()->full_path)->toBe('business/programming');
    expect($webDev->fresh()->full_path)->toBe('business/programming/web-development');
    expect($post->fresh()->full_path)->toBe('business/programming/web-development/web-tutorial');
});

it('maintains path integrity with multiple categories', function () {
    $author = Author::factory()->create();

    // Create multiple 4-level deep structures
    $structures = [];
    foreach (['Technology', 'Science', 'Business'] as $root) {
        $level1 = Category::factory()->create(['name' => $root]);
        $level2 = Category::factory()->create([
            'name' => 'Programming',
            'parent_category_id' => $level1->id,
        ]);
        $level3 = Category::factory()->create([
            'name' => 'Web Development',
            'parent_category_id' => $level2->id,
        ]);
        $level4 = Category::factory()->create([
            'name' => 'Frameworks',
            'parent_category_id' => $level3->id,
        ]);

        $structures[$root] = [
            'level1' => $level1,
            'level4' => $level4,
            'post' => Post::factory()->create([
                'title' => "$root Guide",
                'category_id' => $level4->id,
                'author_id' => $author->id,
            ]),
        ];
    }

    // Verify all paths are correct
    expect($structures['Technology']['post']->full_path)
        ->toBe('technology/programming/web-development/frameworks/technology-guide');
    expect($structures['Science']['post']->full_path)
        ->toBe('science/programming/web-development/frameworks/science-guide');
    expect($structures['Business']['post']->full_path)
        ->toBe('business/programming/web-development/frameworks/business-guide');
});

it('returns root level categories', function () {
    Category::factory()->create(['name' => 'Technology']);
    Category::factory()->create(['name' => 'Science']);

    $tree = LaravelPosts::getCategoryTree();

    expect($tree)->toHaveCount(2);
    expect($tree->pluck('name')->toArray())->toContain('Technology', 'Science');
});

it('builds hierarchical structure correctly', function () {
    $parent = Category::factory()->create(['name' => 'Technology']);
    $child1 = Category::factory()->create([
        'name' => 'Web Development',
        'parent_category_id' => $parent->id,
    ]);
    $child2 = Category::factory()->create([
        'name' => 'Mobile Development',
        'parent_category_id' => $parent->id,
    ]);

    $tree = LaravelPosts::getCategoryTree();

    expect($tree)->toHaveCount(1);

    /** @var Category $firstCategory */
    $firstCategory = $tree->first();
    $firstCategory = $firstCategory->fresh();

    expect($firstCategory->name)->toBe('Technology');
    expect($firstCategory->child_categories)->toHaveCount(2);
    expect($firstCategory->child_categories->pluck('name')->toArray())
        ->toContain('Web Development', 'Mobile Development');
});

it('returns subtree from specific category', function () {
    $parent = Category::factory()->create([
        'name' => 'Technology',
        'description' => null,
        'seo_description' => null,
    ]);
    $child = Category::factory()->create([
        'name' => 'Web Development',
        'parent_category_id' => $parent->id,
        'description' => null,
        'seo_description' => null,
    ]);
    $grandchild = Category::factory()->create([
        'name' => 'Frontend',
        'parent_category_id' => $child->id,
        'description' => null,
        'seo_description' => null,
    ]);

    $tree = LaravelPosts::getCategoryTree($parent);

    /** @var Category $firstCategory */
    $firstCategory = $tree->first();
    $firstCategory = $firstCategory->fresh();

    expect($tree)->toHaveCount(1);
    expect($firstCategory->name)->toBe('Technology');
    expect($firstCategory->child_categories->first()->name)->toBe('Web Development');
    expect($firstCategory->child_categories->first()->child_categories->first()->name)->toBe('Frontend');
});

it('includes posts when requested', function () {
    $author = Author::factory()->create();
    $category = Category::factory()->create(['name' => 'Technology']);

    Post::factory()->create([
        'title' => 'First Post',
        'category_id' => $category->id,
        'author_id' => $author->id,
    ]);
    Post::factory()->create([
        'title' => 'Second Post',
        'category_id' => $category->id,
        'author_id' => $author->id,
    ]);

    $tree = LaravelPosts::getCategoryTree(null, 10, true);

    expect($tree->first()->relationLoaded('posts'))->toBeTrue();
    expect($tree->first()->posts)->toHaveCount(2);
});

it('excludes posts by default', function () {
    $author = Author::factory()->create();
    $category = Category::factory()->create(['name' => 'Technology']);

    Post::factory()->create([
        'category_id' => $category->id,
        'author_id' => $author->id,
    ]);

    $tree = LaravelPosts::getCategoryTree();

    expect($tree->first()->relationLoaded('posts'))->toBeFalse();
});

it('orders categories by sort_order then name', function () {
    Category::factory()->create(['name' => 'Zebra', 'sort_order' => 3]);
    Category::factory()->create(['name' => 'Apple', 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Beta', 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Charlie', 'sort_order' => 2]);

    $tree = LaravelPosts::getCategoryTree();

    $names = $tree->pluck('name')->toArray();
    expect($names)->toBe(['Apple', 'Beta', 'Charlie', 'Zebra']);
});

it('handles posts in nested categories when includePosts is true', function () {
    $author = Author::factory()->create();

    $parent = Category::factory()->create([
        'name' => 'Technology',
        'description' => null,
        'seo_description' => null,
    ]);
    $child = Category::factory()->create([
        'name' => 'Web Development',
        'parent_category_id' => $parent->id,
        'description' => null,
        'seo_description' => null,
    ]);

    Post::factory()->create([
        'title' => 'Parent Post',
        'category_id' => $parent->id,
        'author_id' => $author->id,
    ]);
    Post::factory()->create([
        'title' => 'Child Post',
        'category_id' => $child->id,
        'author_id' => $author->id,
    ]);

    $tree = LaravelPosts::getCategoryTree(null, 10, true);

    /** @var Category $firstCategory */
    $firstCategory = $tree->first();
    $firstCategory = $firstCategory->fresh();

    expect($firstCategory->posts)->toHaveCount(1);
    expect($firstCategory->posts->first()->title)->toBe('Parent Post');
    expect($firstCategory->child_categories->first()->posts)->toHaveCount(1);
    expect($firstCategory->child_categories->first()->posts->first()->title)->toBe('Child Post');
});

it('works with depth limited subtree from specific category', function () {
    $level1 = Category::factory()->create(['name' => 'Level 1']);
    $level2 = Category::factory()->create([
        'name' => 'Level 2',
        'parent_category_id' => $level1->id,
        'description' => null,
        'seo_description' => null,
    ]);
    $level3 = Category::factory()->create([
        'name' => 'Level 3',
        'parent_category_id' => $level2->id,
        'description' => null,
        'seo_description' => null,
    ]);
    Category::factory()->create([
        'name' => 'Level 4',
        'parent_category_id' => $level3->id,
        'description' => null,
        'seo_description' => null,
    ]);

    $tree = LaravelPosts::getCategoryTree($level1);

    /** @var Category $firstCategory */
    $firstCategory = $tree->first();
    $firstCategory = $firstCategory->fresh();

    expect($firstCategory->name)->toBe('Level 1');
    expect($firstCategory->child_categories)->toHaveCount(1);
    expect($firstCategory->child_categories->first()->name)->toBe('Level 2');
});
