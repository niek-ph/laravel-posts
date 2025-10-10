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
            'description' => $this->faker->optional()->sentence(),
            'parent_category_id' => null, // Can be overridden in tests
            'featured_image' => $this->faker->optional()->imageUrl(300, 200, 'business'),
            'seo_title' => $name,
            'seo_description' => $this->faker->paragraphs(5, true),
        ];
    }
}
