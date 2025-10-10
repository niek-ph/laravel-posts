<?php

namespace NiekPH\LaravelPosts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NiekPH\LaravelPosts\Models\Comment;
use NiekPH\LaravelPosts\Models\Post;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->paragraph(),
            'is_approved' => $this->faker->boolean(),
            'author_name' => $this->faker->optional()->name(),
            'author_email' => $this->faker->optional()->email(),
            'post_id' => Post::factory(),
        ];
    }

    public function withRating(int $rating): static
    {
        return $this->state(function (array $attributes) use ($rating) {
            return [
                'rating' => max(0, min(5, $rating)), // Ensure ratings are between 0 and 5
            ];
        });
    }

    public function withoutComment(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'comment' => null,
            ];
        });
    }
}
