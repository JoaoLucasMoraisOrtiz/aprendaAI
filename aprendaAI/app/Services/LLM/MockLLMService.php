<?php
// app/Services/LLM/MockLLMService.php
namespace App\Services\LLM;

class MockLLMService implements LLMServiceInterface
{
    public function generateContent(string $prompt, array $options = []): array
    {
        return [
            'content' => "Mock content generated for prompt: $prompt", 
            'tokens' => 100,
            'model' => 'mock-model-v1',
            'status' => 'success'
        ];
    }
    
    public function generateExplanation(string $question, string $userLevel, array $context = []): string
    {
        return "Mock explanation for question: $question at $userLevel level";
    }
    
    public function analyzePerformance(array $userAnswers, array $userProgress): array
    {
        return [
            'strengths' => ['Mock strength 1', 'Mock strength 2'],
            'weaknesses' => ['Mock weakness 1', 'Mock weakness 2'],
            'recommendations' => ['Study topic X', 'Review concept Y']
        ];
    }
    
    public function generateStudyPlan(array $userProfile, array $topics, array $preferences = []): array
    {
        return [
            'title' => "Study plan for " . ($userProfile['name'] ?? 'User'),
            'goal' => 'Improve understanding of key concepts',
            'duration' => '4 weeks',
            'sessions' => [
                [
                    'topic' => $topics[0]['name'] ?? 'Topic 1',
                    'duration' => '60 minutes',
                    'date' => now()->addDays(1)->format('Y-m-d'),
                    'resources' => ['MockResource 1', 'MockResource 2']
                ],
                [
                    'topic' => $topics[1]['name'] ?? 'Topic 2',
                    'duration' => '45 minutes',
                    'date' => now()->addDays(2)->format('Y-m-d'),
                    'resources' => ['MockResource 3', 'MockResource 4']
                ]
            ]
        ];
    }
}
