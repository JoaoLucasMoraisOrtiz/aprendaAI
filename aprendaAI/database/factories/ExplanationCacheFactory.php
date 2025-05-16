<?php

namespace Database\Factories;

use App\Models\ExplanationCache;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExplanationCache>
 */
class ExplanationCacheFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExplanationCache::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'explanation' => $this->faker->paragraph(5),
            'difficulty_level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'is_personalized' => $this->faker->boolean(20),
        ];
    }
}
