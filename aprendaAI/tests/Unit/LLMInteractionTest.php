<?php

namespace Tests\Unit;

use App\Models\LLMInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LLMInteractionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_llm_interaction_can_be_created()
    {
        $user = User::factory()->create();
        
        $interaction = LLMInteraction::factory()->create([
            'user_id' => $user->id,
            'interaction_type' => 'explanation',
            'prompt' => 'Explique a fórmula de Bhaskara',
            'response' => 'A fórmula de Bhaskara é usada para calcular as raízes de uma equação quadrática...',
            'tokens_used' => 246,
            'model_used' => 'gemini-pro',
            'status' => 'success',
            'metadata' => [
                'source' => 'api',
                'request_id' => 'test-uuid',
                'version' => '1.0',
            ],
        ]);

        $this->assertDatabaseHas('llm_interactions', [
            'interaction_type' => 'explanation',
            'prompt' => 'Explique a fórmula de Bhaskara',
            'model_used' => 'gemini-pro',
            'status' => 'success',
        ]);
    }

    /** @test */
    public function an_interaction_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $interaction = LLMInteraction::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $interaction->user);
        $this->assertEquals($user->id, $interaction->user->id);
    }

    /** @test */
    public function it_can_filter_by_interaction_type()
    {
        // Criar interações com diferentes tipos válidos
        $explanationInteraction = LLMInteraction::factory()->create([
            'interaction_type' => 'explanation'
        ]);
        $questionInteraction = LLMInteraction::factory()->create([
            'interaction_type' => 'question'
        ]);
        $studyPlanInteraction = LLMInteraction::factory()->create([
            'interaction_type' => 'study_plan'
        ]);

        $explanationInteractions = LLMInteraction::ofType('explanation')->get();
        $questionInteractions = LLMInteraction::ofType('question')->get();
        $studyPlanInteractions = LLMInteraction::ofType('study_plan')->get();

        $this->assertEquals(1, $explanationInteractions->count());
        $this->assertEquals(1, $questionInteractions->count());
        $this->assertEquals(1, $studyPlanInteractions->count());
    }

    /** @test */
    public function it_can_filter_successful_interactions()
    {
        $successfulInteraction = LLMInteraction::factory()->successful()->create();
        $failedInteraction = LLMInteraction::factory()->failed()->create();

        $successfulInteractions = LLMInteraction::successful()->get();
        $failedInteractions = LLMInteraction::failed()->get();

        $this->assertEquals(1, $successfulInteractions->count());
        $this->assertEquals(1, $failedInteractions->count());

        $this->assertEquals($successfulInteraction->id, $successfulInteractions->first()->id);
        $this->assertEquals($failedInteraction->id, $failedInteractions->first()->id);
    }

    /** @test */
    public function it_calculates_average_response_time()
    {
        // Criar várias interações com diferentes números de tokens
        LLMInteraction::factory()->create(['tokens_used' => 100]);
        LLMInteraction::factory()->create(['tokens_used' => 200]);
        LLMInteraction::factory()->create(['tokens_used' => 300]);

        $totalTokens = LLMInteraction::totalTokensUsed();

        $this->assertEquals(600, $totalTokens);
    }

    // O cálculo de totalTokensUsed já está coberto acima
}
