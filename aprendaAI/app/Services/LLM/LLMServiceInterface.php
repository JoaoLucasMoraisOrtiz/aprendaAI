<?php
// app/Services/LLM/LLMServiceInterface.php
namespace App\Services\LLM;

interface LLMServiceInterface
{
    public function generateContent(string $prompt, array $options = []): array;
    public function generateExplanation(string $question, string $userLevel, array $context = []): string;
    public function analyzePerformance(array $userAnswers, array $userProgress): array;
    public function generateStudyPlan(array $userProfile, array $topics, array $preferences = []): array;
}