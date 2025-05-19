<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProgressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserProgress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'proficiency_level' => $this->faker->numberBetween(0, 100),
            'last_activity_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'mastery_status' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'questions_answered' => $this->faker->numberBetween(0, 50),
            'questions_correct' => function (array $attributes) {
                return $this->faker->numberBetween(0, $attributes['questions_answered']);
            },
            'adaptive_recommendations' => json_encode(['next_steps' => 'Review basics']),
            'focus_areas' => json_encode([]),
            'learning_streak' => $this->faker->numberBetween(0, 10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set a specific mastery status
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withMasteryStatus(string $status)
    {
        return $this->state(function (array $attributes) use ($status) {
            return [
                'mastery_status' => $status,
            ];
        });
    }

    /**
     * Set a specific proficiency level range
     *
     * @param int $min
     * @param int $max
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withProficiencyLevel(int $min, int $max)
    {
        return $this->state(function (array $attributes) use ($min, $max) {
            return [
                'proficiency_level' => $this->faker->numberBetween($min, $max),
            ];
        });
    }
}
