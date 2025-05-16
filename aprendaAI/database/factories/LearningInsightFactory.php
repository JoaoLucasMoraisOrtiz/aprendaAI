<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningInsight>
 */
class LearningInsightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'insight_type' => $this->faker->randomElement(['strengths', 'weaknesses', 'progress', 'recommendation']),
            'data' => [
                'content' => $this->faker->paragraph(),
                'metrics' => [
                    'score' => $this->faker->numberBetween(0, 100),
                    'progress' => $this->faker->numberBetween(0, 100) . '%',
                ],
                'details' => $this->faker->sentences(3, true),
            ],
            'generated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
