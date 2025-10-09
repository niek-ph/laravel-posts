<?php

namespace NiekPH\LaravelPosts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NiekPH\LaravelPosts\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => $name,
            //            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->optional()->sentence(),
            'parent_category_id' => null, // Can be overridden in tests
        ];
    }

    public function withParent(?Category $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            return [
                'parent_category_id' => $parent?->id ?? Category::factory()->create()->id,
            ];
        });
    }
}
