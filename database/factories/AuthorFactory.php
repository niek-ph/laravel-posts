<?php

namespace NiekPH\LaravelPosts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NiekPH\LaravelPosts\Models\Author;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        $name = $this->faker->name();

        return [
            'name' => $name,
            'slug' => $this->faker->unique()->slug(),
            'email' => $this->faker->unique()->safeEmail(),
            'profile_picture' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'description' => $this->faker->optional()->paragraph(),
            'role' => $this->faker->optional()->randomElement(['author', 'editor', 'contributor', 'admin']),
        ];
    }
}
