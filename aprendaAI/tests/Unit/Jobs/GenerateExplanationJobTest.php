<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateExplanationJob;
use App\Models\Question;
use App\Models\User;
use App\Models\ExplanationCache;
use App\Models\LLMInteraction;
use App\Services\LLM\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\BeforeEach;
use PHPUnit\Framework\Attributes\AfterEach;
use Tests\TestCase;

class GenerateExplanationJobTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $question;
    protected $geminiService;

    #[BeforeEach]
    public function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->question = Question::factory()->create([
            'content' => 'Qual é a integral de x² dx?',
            'difficulty_level' => 'medium'
        ]);
        $this->geminiService = Mockery::mock(GeminiService::class);
        $this->geminiService->allows('getModelName')->andReturn('test-gemini-model');
        $this->app->instance(GeminiService::class, $this->geminiService);
    }
    
    #[AfterEach]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_generates_and_caches_explanation()
    {
        // Simular uma resposta do Gemini
        $geminiResponse = [
            'success' => true,
            'content' => "Para resolver a integral de x² dx, aplicamos a fórmula básica de integração de potências:\n\n∫xⁿ dx = (xⁿ⁺¹)/(n+1) + C, onde n ≠ -1\n\nNo nosso caso, n = 2, então:\n\n∫x² dx = x³/3 + C\n\nOnde C é a constante de integração.\n\nEsta é uma aplicação direta da regra de potência para integrais indefinidas.",
            'usage' => [
                'total_tokens' => 350
            ]
        ];
        
        // Configurar o mock para retornar a resposta simulada
        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn($geminiResponse);
        
        // Disparar o job
        $job = new GenerateExplanationJob($this->question->id, $this->user->id);
        $job->handle($this->geminiService);
        
        // Verificar se a explicação foi cacheada
        $this->assertDatabaseHas('explanation_cache', [
            'question_id' => $this->question->id,
            'difficulty_level' => 'medium',
            'is_personalized' => false
        ]);
        
        // Verificar o conteúdo da explicação
        $explanation = ExplanationCache::where('question_id', $this->question->id)->first();
        $this->assertNotNull($explanation);
        $this->assertStringContainsString('∫x² dx = x³/3 + C', $explanation->explanation);
        
        // Verificar se a interação com o LLM foi registrada
        $this->assertDatabaseHas('llm_interactions', [
            'user_id' => $this->user->id,
            'interaction_type' => 'explanation',
            'model_used' => 'test-gemini-model',
            'status' => 'success',
            'tokens_used' => 350
        ]);
    }

    #[Test]
    public function it_handles_errors_from_gemini_service()
    {
        // Simular uma resposta com erro do Gemini
        $errorResponse = [
            'success' => false,
            'error' => 'API error: service unavailable',
            'content' => null
        ];
        
        // Configurar o mock para retornar a resposta com erro
        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn($errorResponse);
        
        // Disparar o job
        $job = new GenerateExplanationJob($this->question->id, $this->user->id);
        $job->handle($this->geminiService);
        
        // Verificar se a interação com o LLM foi registrada como falha
        $this->assertDatabaseHas('llm_interactions', [
            'user_id' => $this->user->id,
            'interaction_type' => 'explanation',
            'status' => 'failed'
        ]);
        
        // Verificar que nenhuma explicação foi cacheada
        $this->assertDatabaseMissing('explanation_cache', [
            'question_id' => $this->question->id
        ]);
    }

    #[Test]
    public function it_generates_personalized_explanation_based_on_user_profile()
    {
        // Definir um perfil de usuário com características específicas usando o factory
        $this->user = User::factory()
            ->withLearningPreferences('visual', 'simplified', [
                'education_level' => 'high_school',
                'preferred_examples' => 'practical'
            ])
            ->create();
        
        // Garantir que o usuário tem os atributos corretos
        $this->assertEquals('visual', $this->user->learning_style);
        $this->assertEquals('simplified', $this->user->difficulty_preference);
        
        // Simular uma resposta personalizada do Gemini
        $geminiResponse = [
            'success' => true,
            'content' => "Vamos visualizar a integral de x² dx de uma forma simplificada, ideal para estudantes do ensino médio:\n\n[IMAGEM REPRESENTANDO A ÁREA SOB A CURVA]\n\nA integral de x² representa a área sob a curva da função f(x) = x².\n\nAplicando a regra básica de integração:\n∫x² dx = x³/3 + C\n\nExemplo prático: Imagine que você está calculando a distância percorrida por um objeto com aceleração constante. Se a função da velocidade for v(t) = t², então a posição será dada por ∫t² dt = t³/3 + C.",
            'usage' => [
                'total_tokens' => 420
            ]
        ];
        
        // Configurar o mock para retornar a resposta personalizada
        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn($geminiResponse);
        
        // Disparar o job com uma dificuldade personalizada
        $job = new GenerateExplanationJob($this->question->id, $this->user->id, 'simplified', true);
        
        // Testar se o job foi configurado corretamente
        $this->assertEquals($this->question->id, $job->questionId);
        $this->assertEquals($this->user->id, $job->userId);
        $this->assertEquals('simplified', $job->difficultyLevel);
        $this->assertTrue($job->isPersonalized);
        
        $job->handle($this->geminiService);
        
        // Verificar se a explicação personalizada foi cacheada
        $this->assertDatabaseHas('explanation_cache', [
            'question_id' => $this->question->id,
            'difficulty_level' => 'simplified',
            'is_personalized' => true
        ]);
        
        // Verificar o conteúdo da explicação personalizada
        $explanation = ExplanationCache::where('question_id', $this->question->id)
            ->where('difficulty_level', 'simplified')
            ->first();
            
        $this->assertNotNull($explanation);
        $this->assertStringContainsString('forma simplificada', $explanation->explanation);
        $this->assertStringContainsString('Exemplo prático', $explanation->explanation);
    }
}
