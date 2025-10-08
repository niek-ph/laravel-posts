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
            'slug' => $this->faker->unique()->slug(),
            'subtitle' => $this->faker->optional()->sentence(),
            'is_published' => $this->faker->boolean(70),
            'body' => $this->faker->paragraphs(5, true),
            'cover_image' => $this->faker->optional()->imageUrl(800, 600, 'business'),
            'image_thumbnail' => $this->faker->optional()->imageUrl(300, 200, 'business'),
            'author_id' => Author::factory(),
            'category_id' => Category::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => true,
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
