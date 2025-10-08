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
            'stars' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->paragraph(),
            'post_id' => Post::factory(),
        ];
    }

    public function withRating(int $stars): static
    {
        return $this->state(function (array $attributes) use ($stars) {
            return [
                'stars' => max(0, min(5, $stars)), // Ensure stars are between 0 and 5
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
