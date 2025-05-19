<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Answer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'content' => $this->faker->sentence(), // This will be the actual answer text
            'is_correct' => $this->faker->boolean(25), // 25% chance of being correct
            'feedback' => json_encode(['message' => $this->faker->paragraph()]),
            'metadata' => json_encode(['order' => $this->faker->numberBetween(1, 4)]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the answer is correct.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function correct()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_correct' => true,
            ];
        });
    }

    /**
     * Indicate that the answer is incorrect.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function incorrect()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_correct' => false,
            ];
        });
    }
}
