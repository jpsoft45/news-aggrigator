<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'category' => $this->faker->randomElement(['Elections']),
            'author' => $this->faker->name(),
            'source_id' => Source::factory(),
            'published_at' => $this->faker->date(),
            'url' => $this->faker->url(),
            // Add other article fields as necessary
        ];
    }
}
