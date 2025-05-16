<?php

namespace Database\Factories;

use App\Models\LLMInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LLMInteractionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LLMInteraction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'interaction_type' => $this->faker->randomElement([
                'explanation',
                'question',
                'study_plan',
                'recommendation',
                'performance_analysis',
                'performance_analysis_skipped',
            ]),
            'prompt' => $this->faker->paragraph(),
            'response' => $this->faker->paragraph(5),
            'tokens_used' => $this->faker->numberBetween(50, 1000),
            'model_used' => $this->faker->randomElement(['gemini-pro', 'gpt-4', 'claude-3']),
            'status' => $this->faker->randomElement(['success', 'failed', 'processing']),
            'metadata' => json_encode([
                'source' => $this->faker->randomElement(['api', 'web', 'app']),
                'request_id' => $this->faker->uuid(),
                'version' => '1.0',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the interaction was successful.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function successful()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'success',
            ];
        });
    }

    /**
     * Indicate that the interaction failed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
            ];
        });
    }

    /**
     * Set the interaction type.
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function type(string $type)
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'interaction_type' => $type,
            ];
        });
    }
}
