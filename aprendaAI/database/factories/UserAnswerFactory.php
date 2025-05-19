<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAnswerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);
        
        return [
            'user_id' => User::factory(),
            'question_id' => $question->id,
            'answer_id' => $answer->id,
            'is_correct' => $this->faker->boolean(60), // 60% chance of being correct
            'time_spent' => $this->faker->numberBetween(10, 300), // seconds
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
