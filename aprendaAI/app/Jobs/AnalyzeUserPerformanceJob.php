<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\LearningInsight;
use App\Models\LLMInteraction;
use App\Services\LLM\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception; // Importar a classe Exception global
use Throwable;

class AnalyzeUserPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService): void
    {
        $prompt = 'Error before prompt generation'; // Inicializar prompt para o bloco catch
        try {
            $user = User::findOrFail($this->userId);
            // Certifique-se de que a relação 'answers' e 'question.topic' existam e estejam corretas nos Models
            $userAnswers = $user->answers()->with(['question.topic'])->get();

            if ($userAnswers->isEmpty()) {
                LLMInteraction::create([
                    'user_id' => $this->userId,
                    'interaction_type' => 'performance_analysis_skipped',
                    'prompt' => 'No user answers found to analyze.',
                    'response' => null,
                    'status' => 'success', // Job logic completed, no analysis needed
                    'metadata' => ['message' => 'No answers to analyze'],
                    'tokens_used' => 0, // Renamed from token_usage
                    'model_used' => $geminiService->getModelName(), // Renamed from model_version
                ]);
                return;
            }

            $performanceData = [];
            foreach ($userAnswers as $answer) {
                $topicName = $answer->question && $answer->question->topic ? $answer->question->topic->name : 'Unknown Topic';
                if (!isset($performanceData[$topicName])) {
                    $performanceData[$topicName] = ['correct' => 0, 'total' => 0, 'times' => []];
                }
                $performanceData[$topicName]['total']++;
                if ($answer->is_correct) {
                    $performanceData[$topicName]['correct']++;
                }
                $performanceData[$topicName]['times'][] = $answer->time_spent;
            }

            $promptSegments = ["User ID: {$this->userId} performance analysis request."];
            foreach ($performanceData as $topic => $data) {
                $accuracy = $data['total'] > 0 ? ($data['correct'] / $data['total']) * 100 : 0;
                $avgTime = !empty($data['times']) ? array_sum($data['times']) / count($data['times']) : 0;
                $promptSegments[] = sprintf(
                    "Topic: %s - Questions: %d, Correct: %d (%.2f%%), Avg Time: %.2fs.",
                    $topic,
                    $data['total'],
                    $data['correct'],
                    $accuracy,
                    $avgTime
                );
            }
            $prompt = implode("\\n", $promptSegments);
            
            $geminiResponse = $geminiService->generate($prompt);

            if ($geminiResponse['success']) {
                $parsedContent = json_decode($geminiResponse['content'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Failed to parse Gemini response content as JSON: ' . json_last_error_msg());
                }

                LearningInsight::create([
                    'user_id' => $this->userId,
                    'insight_type' => 'performance_analysis',
                    'data' => $parsedContent,
                    'generated_at' => now(),
                ]);

                LLMInteraction::create([
                    'user_id' => $this->userId,
                    'interaction_type' => 'performance_analysis',
                    'prompt' => $prompt,
                    'response' => $geminiResponse['content'],
                    'status' => 'success',
                    'tokens_used' => $geminiResponse['usage']['total_tokens'] ?? 0,
                    'model_used' => $geminiService->getModelName(),
                ]);
            } else {
                // Usar Exception global ou importar no topo do arquivo
                throw new Exception($geminiResponse['error'] ?? 'GeminiService failed without specific error.');
            }

        } catch (Throwable $e) {
            Log::error("AnalyzeUserPerformanceJob failed for user {$this->userId}: " . $e->getMessage());
            LLMInteraction::create([
                'user_id' => $this->userId,
                'interaction_type' => 'performance_analysis',
                'prompt' => $prompt, // Usar o prompt inicializado ou o gerado
                'response' => null,
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
                'tokens_used' => 0,
                'model_used' => isset($geminiService) ? $geminiService->getModelName() : 'unknown',
            ]);
        }
    }
}
