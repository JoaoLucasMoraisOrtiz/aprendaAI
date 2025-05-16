<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Exam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'year' => $this->faker->numberBetween(2015, 2025),
            'date' => $this->faker->dateTimeBetween('-5 years', 'now'),
        ];
    }
}
