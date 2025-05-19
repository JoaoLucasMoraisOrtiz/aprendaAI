<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AnalyzeUserPerformanceJob;
use App\Models\User;
use App\Models\Topic;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\LearningInsight;
use App\Models\LLMInteraction;
use App\Services\LLM\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyzeUserPerformanceJobTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $geminiService;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->geminiService = Mockery::mock(GeminiService::class);
        $this->geminiService->allows('getModelName')->andReturn('test-gemini-model'); // Allow getModelName
        $this->app->instance(GeminiService::class, $this->geminiService);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_analyzes_user_performance_and_generates_insights()
    {
        // Criar tópicos e questões para o teste
        $topic = Topic::factory()->create([
            'name' => 'Álgebra Linear'
        ]);
        
        $questions = Question::factory()->count(5)->create([
            'topic_id' => $topic->id
        ]);
        
        // Criar algumas respostas do usuário (3 corretas, 2 incorretas)
        foreach ($questions as $index => $question) {
            UserAnswer::factory()->create([
                'user_id' => $this->user->id,
                'question_id' => $question->id,
                'is_correct' => $index < 3, // Primeiras 3 respostas corretas
                'time_spent' => ($index + 1) * 30, // Tempos crescentes para teste
                // 'answer_date' => now()->subDays($index) // Respostas em dias diferentes // Coluna removida
            ]);
        }
        
        // Criar um JSON de resposta simulado do Gemini
        $geminiResponse = [
            'success' => true,
            'content' => '{
                "topic_performance": {
                    "Álgebra Linear": {
                        "accuracy": 60,
                        "avg_time": 90,
                        "mastery": "intermediate",
                        "strengths": ["Resolução rápida", "Consistência"],
                        "weaknesses": ["Questões mais complexas", "Conceitos avançados"]
                    }
                },
                "overall_assessment": "O estudante demonstra um bom domínio básico de Álgebra Linear, mas precisa focar em conceitos mais avançados.",
                "recommendations": [
                    "Revisar os conceitos de espaços vetoriais",
                    "Praticar mais transformações lineares",
                    "Focar em exercícios de nível intermediário a avançado"
                ],
                "learning_strategy": "Recomenda-se um foco em aplicações práticas e resolução de problemas complexos"
            }',
            'usage' => [
                'total_tokens' => 720
            ]
        ];
        
        // Configurar o mock para retornar a resposta simulada
        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn($geminiResponse);
        // $this->geminiService->shouldReceive('getModelName')->andReturn('gemini-pro'); // No longer needed here explicitly if globally allowed

        // Disparar o job
        $job = new AnalyzeUserPerformanceJob($this->user->id);
        $job->handle($this->geminiService);
        
        // Verificar se o insight foi criado
        $this->assertDatabaseHas('learning_insights', [
            'user_id' => $this->user->id,
            'insight_type' => 'performance_analysis'
        ]);
        
        // Verificar o conteúdo do insight
        $insight = LearningInsight::where('user_id', $this->user->id)->first();
        $this->assertNotNull($insight);
        
        $insightData = $insight->data;
        $this->assertArrayHasKey('topic_performance', $insightData);
        $this->assertArrayHasKey('overall_assessment', $insightData);
        $this->assertArrayHasKey('recommendations', $insightData);
        
        // Verificar se a interação com o LLM foi registrada
        $this->assertDatabaseHas('llm_interactions', [
            'user_id' => $this->user->id,
            'interaction_type' => 'performance_analysis',
            'model_used' => 'test-gemini-model',
            'status' => 'success',
            'tokens_used' => 720
        ]);
    }

    #[Test]
    public function it_handles_errors_from_gemini_service()
    {
        // Criar tópicos e questões para o teste
        $topic = Topic::factory()->create();
        $question = Question::factory()->create(['topic_id' => $topic->id]);
        
        // Criar uma resposta do usuário
        UserAnswer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question->id
        ]);
        
        // Simular uma resposta com erro do Gemini
        $errorResponse = [
            'success' => false,
            'error' => 'API error: invalid request',
            'content' => null
        ];
        
        // Configurar o mock para retornar a resposta com erro
        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn($errorResponse);
        // $this->geminiService->shouldReceive('getModelName')->andReturn('gemini-pro'); // No longer needed here

        // Disparar o job
        $job = new AnalyzeUserPerformanceJob($this->user->id);
        $job->handle($this->geminiService);
        
        // Verificar se a interação com o LLM foi registrada como falha
        $this->assertDatabaseHas('llm_interactions', [
            'user_id' => $this->user->id,
            'interaction_type' => 'performance_analysis',
            'status' => 'failed',
            'model_used' => 'test-gemini-model'
        ]);
        
        // Verificar que nenhum insight foi criado
        $this->assertDatabaseMissing('learning_insights', [
            'user_id' => $this->user->id,
            'insight_type' => 'performance_analysis'
        ]);
    }

    #[Test]
    public function it_does_not_analyze_when_no_user_answers_exist()
    {
        // Não criar nenhuma resposta do usuário
        
        // Disparar o job
        $job = new AnalyzeUserPerformanceJob($this->user->id);
        $job->handle($this->geminiService);
        
        // Verificar que nenhuma chamada foi feita ao serviço LLM
        $this->geminiService->shouldNotHaveReceived('generate');
        // $this->geminiService->shouldNotHaveReceived('getModelName'); // This might be called if an unexpected error occurs before early exit

        // Verificar que nenhum insight foi criado
        $this->assertDatabaseMissing('learning_insights', [
            'user_id' => $this->user->id
        ]);
        
        // Verificar que uma interação de skip foi registrada
        $this->assertDatabaseHas('llm_interactions', [
            'user_id' => $this->user->id,
            'interaction_type' => 'performance_analysis_skipped',
            'status' => 'success' // Indicates the job logic for skipping completed successfully
        ]);
    }
}
