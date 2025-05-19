<?php

namespace Database\Factories;

use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudyPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudyPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+2 weeks');
        $endDate = $this->faker->dateTimeBetween($startDate, '+8 weeks');
        
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'goal' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'metadata' => json_encode([
                'focus_areas' => [$this->faker->word(), $this->faker->word()],
            ]),
            'status' => $this->faker->randomElement(['active', 'completed', 'abandoned']),
            'is_adaptive' => $this->faker->boolean(70),
            'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the study plan is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the study plan is completed.
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
}
