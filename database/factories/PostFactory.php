<?php

namespace NiekPH\LaravelPosts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NiekPH\LaravelPosts\Models\Author;
use NiekPH\LaravelPosts\Models\Category;
use NiekPH\LaravelPosts\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(6, true);

        return [
            'title' => $title,
            //            'slug' => $this->faker->unique()->slug(),
            'excerpt' => $this->faker->optional()->sentence(),
            'published_at' => $this->faker->optional()->dateTime(),
            'body' => $this->faker->paragraph(5, true),
            'featured_image' => $this->faker->optional()->imageUrl(300, 200, 'business'),
            'seo_title' => $title,
            'seo_description' => $this->faker->paragraphs(5, true),
            'author_id' => Author::factory(),
            'category_id' => Category::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => now(),
            ];
        });
    }

    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => false,
            ];
        });
    }
}
