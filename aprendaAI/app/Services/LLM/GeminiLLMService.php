<?php

// app/Services/LLM/GeminiLLMService.php
namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\LLMInteraction;

class GeminiLLMService implements LLMServiceInterface
{
    protected $apiKey;
    protected $model;
    protected $endpoint;
    
    public function __construct(string $apiKey, string $model, string $endpoint)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->endpoint = $endpoint;
    }
    
    public function generateContent(string $prompt, array $options = []): array
    {
        $cacheKey = 'llm_' . md5($prompt . json_encode($options));
        
        if (config('llm.cache.enabled') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $url = "{$this->endpoint}/{$this->model}:generateContent?key={$this->apiKey}";
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];
        
        // Merge default options with provided options
        $payload = array_merge($payload, $options);
        
        $response = Http::post($url, $payload);
        
        // Log the interaction
        LLMInteraction::create([
            'prompt' => $prompt,
            'options' => json_encode($options),
            'status' => $response->status(),
            'tokens_used' => $response->json('usage.totalTokens') ?? 0,
        ]);
        
        $result = $response->json();
        
        if (config('llm.cache.enabled')) {
            Cache::put($cacheKey, $result, config('llm.cache.ttl'));
        }
        
        return $result;
    }
    
    public function generateExplanation(string $question, string $userLevel, array $context = []): string
    {
        $prompt = $this->buildExplanationPrompt($question, $userLevel, $context);
        $result = $this->generateContent($prompt);
        
        return $this->parseExplanationResponse($result);
    }
    
    public function analyzePerformance(array $userAnswers, array $userProgress): array
    {
        $prompt = $this->buildPerformanceAnalysisPrompt($userAnswers, $userProgress);
        $result = $this->generateContent($prompt);
        
        return $this->parseAnalysisResponse($result);
    }
    
    public function generateStudyPlan(array $userProfile, array $topics, array $preferences = []): array
    {
        $prompt = $this->buildStudyPlanPrompt($userProfile, $topics, $preferences);
        $result = $this->generateContent($prompt);
        
        return $this->parseStudyPlanResponse($result);
    }
    
    // Helper methods for building prompts and parsing responses
    protected function buildExplanationPrompt(string $question, string $userLevel, array $context): string
    {
        $prompt = "Você é um assistente educacional especializado em criar explicações personalizadas.\n\n";
        $prompt .= "QUESTÃO: {$question}\n\n";
        $prompt .= "NÍVEL DO ESTUDANTE: {$userLevel}\n\n";
        
        if (!empty($context)) {
            $prompt .= "CONTEXTO ADICIONAL:\n";
            
            if (isset($context['previous_errors'])) {
                $prompt .= "Erros anteriores do estudante: " . json_encode($context['previous_errors']) . "\n";
            }
            
            if (isset($context['learning_style'])) {
                $prompt .= "Estilo de aprendizagem preferido: {$context['learning_style']}\n";
            }
            
            if (isset($context['topic_proficiency'])) {
                $prompt .= "Proficiência no tópico: {$context['topic_proficiency']}\n";
            }
            
            if (isset($context['related_topics'])) {
                $prompt .= "Tópicos relacionados: " . implode(", ", $context['related_topics']) . "\n";
            }
        }
        
        $prompt .= "\nInstruções:\n";
        $prompt .= "1. Forneça uma explicação clara e didática sobre o tópico da questão.\n";
        $prompt .= "2. Adapte sua explicação ao nível de conhecimento do estudante.\n";
        $prompt .= "3. Inclua exemplos relevantes que ajudem na compreensão.\n";
        $prompt .= "4. Se houver erros anteriores, aborde especificamente os conceitos mal compreendidos.\n";
        $prompt .= "5. Use linguagem acessível e adequada ao nível do estudante.\n";
        $prompt .= "6. Estruture a explicação de forma lógica, começando com conceitos básicos e progredindo para mais complexos.\n";
        
        return $prompt;
    }
    
    protected function parseExplanationResponse(array $response): string
    {
        // Check if the response has the expected structure
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }
        
        // Fallback in case the response structure is different
        if (isset($response['text'])) {
            return $response['text'];
        }
        
        return "Não foi possível gerar uma explicação. Por favor, tente novamente.";
    }
    
    protected function buildPerformanceAnalysisPrompt(array $userAnswers, array $userProgress): string
    {
        $prompt = "Você é um sistema de análise de aprendizado adaptativo. Analise o desempenho do estudante com base nos dados fornecidos.\n\n";
        
        // Include user answers data
        $prompt .= "HISTÓRICO DE RESPOSTAS DO ESTUDANTE:\n" . json_encode($userAnswers, JSON_PRETTY_PRINT) . "\n\n";
        
        // Include user progress data
        $prompt .= "PROGRESSO DO ESTUDANTE POR TÓPICO:\n" . json_encode($userProgress, JSON_PRETTY_PRINT) . "\n\n";
        
        $prompt .= "Instruções:\n";
        $prompt .= "1. Identifique padrões de erro recorrentes nas respostas do estudante.\n";
        $prompt .= "2. Determine os conceitos fundamentais que o estudante não compreende bem.\n";
        $prompt .= "3. Analise o progresso ao longo do tempo por tópico.\n";
        $prompt .= "4. Identifique pontos fortes e áreas que precisam de melhoria.\n";
        $prompt .= "5. Recomende tópicos específicos para revisão.\n";
        $prompt .= "6. Sugira estratégias de estudo personalizadas com base no padrão de aprendizagem.\n";
        $prompt .= "7. Formate a resposta como um objeto JSON estruturado com as seguintes chaves:\n";
        $prompt .= "   - strengths: array de pontos fortes\n";
        $prompt .= "   - weaknesses: array de áreas para melhoria\n";
        $prompt .= "   - patterns: padrões de erro identificados\n";
        $prompt .= "   - recommendations: recomendações específicas de estudo\n";
        $prompt .= "   - next_topics: array de próximos tópicos sugeridos\n";
        $prompt .= "   - progress_summary: resumo geral do progresso\n";
        
        return $prompt;
    }
    
    protected function parseAnalysisResponse(array $response): array
    {
        // Extract the text content from response
        $content = '';
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];
        } elseif (isset($response['text'])) {
            $content = $response['text'];
        }
        
        // Try to parse the JSON response
        try {
            // Extract JSON from the content (in case there's extra text)
            preg_match('/{.*}/s', $content, $matches);
            
            if (!empty($matches)) {
                $jsonData = json_decode($matches[0], true);
                
                // Ensure all required keys are present
                $requiredKeys = ['strengths', 'weaknesses', 'patterns', 'recommendations', 'next_topics', 'progress_summary'];
                foreach ($requiredKeys as $key) {
                    if (!isset($jsonData[$key])) {
                        $jsonData[$key] = [];
                    }
                }
                
                return $jsonData;
            }
        } catch (\Exception $e) {
            // If parsing fails, return a structured fallback response
        }
        
        // Fallback response if parsing fails
        return [
            'strengths' => [],
            'weaknesses' => [],
            'patterns' => [],
            'recommendations' => ['Revisar os conceitos básicos dos tópicos estudados'],
            'next_topics' => [],
            'progress_summary' => 'Análise detalhada não disponível. Por favor, tente novamente.',
        ];
    }
    
    protected function buildStudyPlanPrompt(array $userProfile, array $topics, array $preferences): string
    {
        $prompt = "Você é um sistema especializado em criar planos de estudo personalizados. Gere um plano de estudos adaptado ao perfil do usuário.\n\n";
        
        // Include user profile data
        $prompt .= "PERFIL DO ESTUDANTE:\n" . json_encode($userProfile, JSON_PRETTY_PRINT) . "\n\n";
        
        // Include topics to be studied
        $prompt .= "TÓPICOS A ESTUDAR:\n" . json_encode($topics, JSON_PRETTY_PRINT) . "\n\n";
        
        // Include user preferences if available
        if (!empty($preferences)) {
            $prompt .= "PREFERÊNCIAS DO ESTUDANTE:\n" . json_encode($preferences, JSON_PRETTY_PRINT) . "\n\n";
        }
        
        $prompt .= "Instruções:\n";
        $prompt .= "1. Crie um plano de estudo personalizado baseado no perfil do estudante.\n";
        $prompt .= "2. Organize os tópicos em uma sequência lógica de aprendizado.\n";
        $prompt .= "3. Considere a proficiência atual e preferências do estudante.\n";
        $prompt .= "4. Para cada tópico, defina objetivos de aprendizado específicos.\n";
        $prompt .= "5. Inclua estimativas de tempo para cada sessão de estudo.\n";
        $prompt .= "6. Sugira recursos adicionais quando apropriado.\n";
        $prompt .= "7. Forneça recomendações de exercícios práticos para cada tópico.\n";
        $prompt .= "8. Formate a resposta como um objeto JSON estruturado com as seguintes chaves:\n";
        $prompt .= "   - title: título do plano de estudos\n";
        $prompt .= "   - overview: visão geral do plano\n";
        $prompt .= "   - duration: duração estimada total (em dias)\n";
        $prompt .= "   - sessions: array de sessões de estudo, cada uma contendo:\n";
        $prompt .= "     * day: dia da sessão\n";
        $prompt .= "     * topics: array de tópicos para o dia\n";
        $prompt .= "     * duration_minutes: duração estimada em minutos\n";
        $prompt .= "     * resources: recursos recomendados\n";
        $prompt .= "     * exercises: exercícios recomendados\n";
        $prompt .= "     * objectives: objetivos de aprendizado\n";
        
        return $prompt;
    }
    
    protected function parseStudyPlanResponse(array $response): array
    {
        // Extract the text content from response
        $content = '';
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];
        } elseif (isset($response['text'])) {
            $content = $response['text'];
        }
        
        // Try to parse the JSON response
        try {
            // Extract JSON from the content (in case there's extra text)
            preg_match('/{.*}/s', $content, $matches);
            
            if (!empty($matches)) {
                $jsonData = json_decode($matches[0], true);
                
                // Ensure all required keys are present
                $requiredKeys = ['title', 'overview', 'duration', 'sessions'];
                foreach ($requiredKeys as $key) {
                    if (!isset($jsonData[$key])) {
                        if ($key === 'sessions') {
                            $jsonData[$key] = [];
                        } elseif ($key === 'duration') {
                            $jsonData[$key] = 7; // Default 7 days
                        } else {
                            $jsonData[$key] = '';
                        }
                    }
                }
                
                return $jsonData;
            }
        } catch (\Exception $e) {
            // If parsing fails, return a structured fallback response
        }
        
        // Fallback response if parsing fails
        return [
            'title' => 'Plano de Estudos Personalizado',
            'overview' => 'Plano de estudos básico para os tópicos solicitados.',
            'duration' => 7,
            'sessions' => [
                [
                    'day' => 1,
                    'topics' => $topics ?? ['Revisão geral'],
                    'duration_minutes' => 60,
                    'resources' => ['Material didático padrão'],
                    'exercises' => ['Exercícios de fixação básicos'],
                    'objectives' => ['Compreender os conceitos fundamentais']
                ]
            ]
        ];
    }
}