<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\Question;
use App\Models\User;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AdaptiveLearningControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user); // Use Sanctum for API authentication
    }

    /** @test */
    public function it_can_list_all_topics()
    {
        // Criar alguns tópicos para testar
        Topic::factory()->count(3)->create([
            'is_active' => true
        ]);
        
        // Criar um tópico inativo que não deve aparecer na lista
        Topic::factory()->create([
            'is_active' => false
        ]);

        $response = $this->getJson('/api/adaptive-learning/topics');
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_can_get_questions_for_a_topic()
    {
        $topic = Topic::factory()->create();
        
        // Criar algumas questões para o tópico
        Question::factory()->count(5)->create([
            'topic_id' => $topic->id
        ]);

        $response = $this->getJson("/api/adaptive-learning/questions/{$topic->id}");
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_can_submit_an_answer()
    {
        $topic = Topic::factory()->create();
        $question = Question::factory()->create([
            'topic_id' => $topic->id
        ]);
        
        // Use the correct() state or set is_correct directly
        $correctAnswer = Answer::factory()->correct()->create([
            'question_id' => $question->id,
        ]);
        
        $answer = [
            'question_id' => $question->id,
            'answer_id' => $correctAnswer->id,
            'time_spent' => 120
        ];

        $response = $this->postJson('/api/adaptive-learning/submit-answer', $answer);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['is_correct', 'message']);
        
        // Verificar se a resposta do usuário foi registrada no banco de dados
        $this->assertDatabaseHas('user_answers', [
            'user_id' => $this->user->id,
            'question_id' => $question->id
        ]);
    }

    /** @test */
    public function it_can_get_explanation_for_a_question()
    {
        $question = Question::factory()->create();

        $response = $this->getJson("/api/adaptive-learning/explanation/{$question->id}");
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data' => ['explanation', 'is_personalized']]);
    }

    /** @test */
    public function it_can_get_performance_analysis()
    {
        $response = $this->getJson("/api/adaptive-learning/performance-analysis");
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function it_can_recommend_next_steps()
    {
        $response = $this->getJson("/api/adaptive-learning/recommendations");
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }
}
