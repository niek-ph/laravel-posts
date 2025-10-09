<?php

use NiekPH\LaravelPosts\Models\Author;
use NiekPH\LaravelPosts\Models\Category;
use NiekPH\LaravelPosts\Models\Post;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
