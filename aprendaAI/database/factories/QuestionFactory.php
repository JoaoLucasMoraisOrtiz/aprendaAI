<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'content' => $this->faker->paragraph(),
            'difficulty_level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'type' => $this->faker->randomElement(['multiple_choice', 'true_false', 'essay']),
            'explanation' => $this->faker->paragraphs(2, true),
            'is_active' => $this->faker->boolean(90),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the question has a specific difficulty level.
     *
     * @param string $level
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function difficulty(string $level)
    {
        return $this->state(function (array $attributes) use ($level) {
            return [
                'difficulty_level' => $level,
            ];
        });
    }

    /**
     * Indicate that the question is of a specific type.
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ofType(string $type)
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type,
            ];
        });
    }
}
