<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateStudyPlanJob;
use App\Models\StudyPlan;
use App\Models\StudySession;
use App\Models\User;
use App\Models\LLMInteraction;
use App\Services\LLM\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GenerateStudyPlanJobTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $geminiService;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->geminiService = Mockery::mock(GeminiService::class);
        $this->app->instance(GeminiService::class, $this->geminiService);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_a_study_plan_based_on_gemini_response()
    {
        // Simular dados de entrada para o job
        $subjects = [
            ['name' => 'Matemática', 'level' => 'intermediário'],
            ['name' => 'Física', 'level' => 'básico']
        ];
        $duration = 4; // 4 semanas
        $goal = 'Preparação para o vestibular';
        $preferences = [
            'tempoEstudoDiario' => '2 horas',
            'focoEspecial' => 'Cálculo e Mecânica'
        ];
        
        // Criar um JSON de resposta simulado do Gemini
        $geminiResponse = [
            'success' => true,
            'content' => '{
                "title": "Plano de Estudos para Vestibular",
                "description": "Plano estratégico de 4 semanas focado em Matemática e Física",
                "duration_weeks": 4,
                "goal": "Preparação para o vestibular",
                "weekly_schedule": [
                    {
                        "week": 1,
                        "focus": "Fundamentos de Matemática",
                        "sessions": [
                            {
                                "day": "Monday",
                                "duration_minutes": 90,
                                "subject": "Matemática",
                                "topic": "Funções do 1º grau",
                                "resources": ["Livro Fundamentos de Matemática", "Videoaula sobre funções"],
                                "activities": ["Resolver exercícios 1-10", "Revisar teoria"]
                            },
                            {
                                "day": "Wednesday",
                                "duration_minutes": 90,
                                "subject": "Física",
                                "topic": "Cinemática",
                                "resources": ["Livro Física Básica", "Simulador online"],
                                "activities": ["Experimento virtual", "Questões conceituais"]
                            }
                        ]
                    }
                ],
                "recommendations": ["Fazer exercícios diariamente", "Revisar conteúdo semanalmente"]
            }',
            'usage' => [
                'total_tokens' => 850
            ]
        ];
        
        // Configurar o mock para retornar a resposta simulada
        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn($geminiResponse);
        
        // Disparar o job
        $job = new GenerateStudyPlanJob($this->user, $subjects, $duration, $goal, $preferences);
        $job->handle($this->geminiService);
        
        // Verificar se o plano de estudos foi criado
        $this->assertDatabaseHas('study_plans', [
            'user_id' => $this->user->id,
            'title' => 'Plano de Estudos para Vestibular',
            'description' => 'Plano estratégico de 4 semanas focado em Matemática e Física',
            'goal' => 'Preparação para o vestibular',
            'is_adaptive' => true
        ]);
        
        // Verificar se as sessões de estudo foram criadas
        $plan = StudyPlan::where('user_id', $this->user->id)->first();
        $this->assertNotNull($plan);
        
        $this->assertDatabaseHas('study_sessions', [
            'study_plan_id' => $plan->id,
            'duration' => 90
        ]);
        
        // Verificar se a interação com o LLM foi registrada
        $this->assertDatabaseHas('llm_interactions', [
            'user_id' => $this->user->id,
            'interaction_type' => 'study_plan',
            'model_used' => 'gemini-pro',
            'status' => 'success',
            'tokens_used' => 850
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_errors_from_gemini_service()
    {
        // Simular dados de entrada para o job
        $subjects = [
            ['name' => 'Matemática', 'level' => 'intermediário']
        ];
        $duration = 2;
        $goal = 'Preparação para prova';
        
        // Simular uma resposta com erro do Gemini
        $errorResponse = [
            'success' => false,
            'error' => 'API error: rate limit exceeded',
            'content' => null
        ];
        
        // Configurar o mock para retornar a resposta com erro
        $this->geminiService->shouldReceive('generate')
            ->once()
            ->andReturn($errorResponse);
        
        // Disparar o job
        $job = new GenerateStudyPlanJob($this->user, $subjects, $duration, $goal);
        $job->handle($this->geminiService);
        
        // Verificar se a interação com o LLM foi registrada como falha
        $this->assertDatabaseHas('llm_interactions', [
            'user_id' => $this->user->id,
            'interaction_type' => 'study_plan',
            'status' => 'failed'
        ]);
        
        // Verificar que nenhum plano de estudos foi criado
        $this->assertDatabaseMissing('study_plans', [
            'user_id' => $this->user->id,
            'goal' => 'Preparação para prova'
        ]);
    }
}
