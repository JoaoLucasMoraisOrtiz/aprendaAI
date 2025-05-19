<?php

namespace App\Jobs;

use App\Models\ExplanationCache;
use App\Models\LLMInteraction;
use App\Models\Question;
use App\Models\User;
use App\Services\LLM\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateExplanationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $questionId;
    public ?int $userId;
    public ?string $difficultyLevel;
    public bool $isPersonalized;

    /**
     * Create a new job instance.
     */
    public function __construct(int $questionId, ?int $userId = null, ?string $difficultyLevel = null, bool $isPersonalized = false)
    {
        $this->questionId = $questionId;
        $this->userId = $userId;
        $this->difficultyLevel = $difficultyLevel;
        $this->isPersonalized = $isPersonalized;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService): void
    {
        try {
            $question = Question::findOrFail($this->questionId);
            $user = $this->userId ? User::find($this->userId) : null;

            // Use the provided difficulty level or fallback to the question's default
            $difficultyLevel = $this->difficultyLevel ?? $question->difficulty_level;
            
            // Log para depuração
            Log::info("GenerateExplanationJob - Iniciando geração", [
                'question_id' => $this->questionId,
                'user_id' => $this->userId,
                'difficulty_level' => $difficultyLevel,
                'is_personalized' => $this->isPersonalized
            ]);

            // Verificar se já existe uma explicação em cache
            $existingExplanation = ExplanationCache::where('question_id', $this->questionId)
                ->where('difficulty_level', $difficultyLevel)
                ->where('is_personalized', $this->isPersonalized)
                ->first();

            if ($existingExplanation) {
                // Usar explicação em cache se existir
                Log::info("GenerateExplanationJob - Explicação encontrada em cache");
                return;
            }

            // Preparar prompt para o Gemini
            $prompt = $this->preparePrompt($question, $user, $difficultyLevel);
            
            // Log do prompt
            Log::info("GenerateExplanationJob - Prompt gerado", [
                'prompt' => $prompt
            ]);

            // Obter explicação do Gemini
            $response = $geminiService->generate($prompt);
            
            // Log da resposta
            Log::info("GenerateExplanationJob - Resposta do Gemini", [
                'success' => $response['success'],
                'content_length' => strlen($response['content'] ?? ''),
                'tokens' => $response['usage']['total_tokens'] ?? 0
            ]);

            if ($response['success']) {
                // Salvar explicação em cache
                $explanationCache = ExplanationCache::create([
                    'question_id' => $this->questionId,
                    'explanation' => $response['content'],
                    'difficulty_level' => $difficultyLevel,
                    'is_personalized' => $this->isPersonalized,
                ]);
                
                // Log do cache criado
                Log::info("GenerateExplanationJob - Cache de explicação criado", [
                    'cache_id' => $explanationCache->id,
                    'question_id' => $explanationCache->question_id,
                    'difficulty_level' => $explanationCache->difficulty_level,
                    'is_personalized' => $explanationCache->is_personalized
                ]);

                // Registrar interação com o LLM
                LLMInteraction::create([
                    'user_id' => $this->userId ?? 1, // Usar ID 1 como fallback
                    'interaction_type' => 'explanation',
                    'prompt' => $prompt,
                    'response' => $response['content'],
                    'model_used' => $geminiService->getModelName(),
                    'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                    'status' => 'success',
                ]);
            } else {
                throw new Exception($response['error'] ?? 'Falha ao gerar explicação');
            }
        } catch (Exception $e) {
            Log::error("Erro ao gerar explicação para questão {$this->questionId}: " . $e->getMessage());
            
            // Registrar erro na interação com o LLM
            if (isset($this->userId)) {
                LLMInteraction::create([
                    'user_id' => $this->userId,
                    'interaction_type' => 'explanation',
                    'prompt' => $prompt ?? "Erro antes da geração do prompt",
                    'response' => null,
                    'model_used' => isset($geminiService) ? $geminiService->getModelName() : 'unknown',
                    'tokens_used' => 0,
                    'status' => 'failed',
                    'metadata' => ['error' => $e->getMessage()],
                ]);
            }
        }
    }

    /**
     * Preparar o prompt para o Gemini.
     */
    private function preparePrompt(Question $question, ?User $user, string $difficultyLevel): string
    {
        $prompt = "Por favor, explique o seguinte conceito/questão:\n\n";
        $prompt .= $question->content . "\n\n";
        
        if ($question->explanation) {
            $prompt .= "Informações adicionais: " . $question->explanation . "\n\n";
        }
        
        $prompt .= "Nível de dificuldade desejado: " . $difficultyLevel . "\n";
        $prompt .= "Tipo de questão: " . $question->type . "\n\n";

        // Adicionar personalização se houver usuário
        if ($user && $this->isPersonalized) {
            // Obter progresso do usuário se disponível
            $progress = $user->progress()->where('topic_id', $question->topic_id)->first();
            
            if ($progress) {
                $prompt .= "Perfil do aluno:\n";
                $prompt .= "Nível de proficiência: " . $progress->proficiency_level . "%\n";
                
                if ($progress->mastery_level) {
                    $prompt .= "Nível de domínio: " . $progress->mastery_level . "\n";
                }
                
                if ($progress->focus_areas) {
                    $prompt .= "Áreas de foco: " . implode(", ", $progress->focus_areas) . "\n";
                }
            }
        }
        
        $prompt .= "\nForneça uma explicação clara, detalhada e didática. Inclua exemplos quando apropriado e mostre o passo a passo da solução.";
        
        return $prompt;
    }
}
