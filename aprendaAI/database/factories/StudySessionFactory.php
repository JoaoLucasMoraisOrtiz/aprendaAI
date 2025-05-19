<?php

namespace Database\Factories;

use App\Models\StudyPlan;
use App\Models\StudySession;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudySessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudySession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'completed', 'skipped']);
        
        return [
            'study_plan_id' => StudyPlan::factory(),
            'topic_id' => Topic::factory(),
            'duration' => $this->faker->numberBetween(30, 120), // minutes
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+2 weeks'),
            'status' => $status,
            'is_completed' => $status === 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the session is completed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    /**
     * Indicate that the session is pending.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the session is skipped.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function skipped()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'skipped',
            ];
        });
    }
}
