<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Topic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'name' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->paragraph(),
            'is_active' => $this->faker->boolean(80),
            'difficulty_level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'order' => $this->faker->numberBetween(1, 100),
            'estimated_time_minutes' => $this->faker->numberBetween(15, 120),
            'prerequisites' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the topic is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the topic has a specific difficulty level.
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
}
