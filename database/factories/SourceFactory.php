<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'description' => 'The New York Times is an American newspaper based in New York City with worldwide influence and readership.',
            'url' => 'https://www.nytimesnews.com',
            // Add other source fields as necessary
        ];
    }
}
