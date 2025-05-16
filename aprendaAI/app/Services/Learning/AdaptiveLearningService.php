<?php
// app/Services/Learning/AdaptiveLearningService.php
namespace App\Services\Learning;

use App\Models\User;
use App\Models\Question;
use App\Models\UserProgress;
use App\Models\LearningInsight;
use App\Services\LLM\LLMServiceInterface;
use App\Models\Topic;

class AdaptiveLearningService
{
    protected $llmService;
    
    public function __construct(LLMServiceInterface $llmService = null)
    {
        $this->llmService = $llmService;
    }
    
    public function getNextQuestions(User $user, int $topicId, int $count = 5): array
    {
        $userProgress = UserProgress::where('user_id', $user->id)
            ->where('topic_id', $topicId)
            ->first();
        
        $proficiencyLevel = $userProgress ? $userProgress->proficiency_level : 0;
        
        // Determine appropriate difficulty based on proficiency
        $difficulty = $this->mapProficiencyToDifficulty($proficiencyLevel);
        
        // Log difficulty and topic for debugging
        \Log::info("Getting questions for topic {$topicId} with difficulty {$difficulty}");
        
        // First try to get questions with the right difficulty
        $questions = Question::where('topic_id', $topicId)
            ->where('difficulty_level', $difficulty)
            ->inRandomOrder()
            ->limit($count)
            ->get();
        
        // If no questions are found, try any difficulty
        if ($questions->isEmpty()) {
            \Log::warning("No questions found with difficulty {$difficulty}, getting any questions");
            $questions = Question::where('topic_id', $topicId)
                ->inRandomOrder()
                ->limit($count)
                ->get();
        }
        
        // Still nothing? Log it but don't crash
        if ($questions->isEmpty()) {
            \Log::error("No questions found at all for topic {$topicId}");
        } else {
            \Log::info("Found " . $questions->count() . " questions");
        }
        
        return $questions->toArray();
    }
    
    public function generatePersonalizedExplanation(User $user, Question $question): string
    {
        $userProgress = UserProgress::where('user_id', $user->id)
            ->where('topic_id', $question->topic_id)
            ->first();
        
        $proficiencyLevel = $userProgress ? $userProgress->proficiency_level : 0;
        $userLevel = $this->mapProficiencyToLevel($proficiencyLevel);
        
        // Get user's answer history for context
        $answerHistory = $this->getUserAnswerContext($user, $question->topic_id);
        
        if ($this->llmService) {
            return $this->llmService->generateExplanation(
                $question->content,
                $userLevel,
                $answerHistory
            );
        }
        
        // Return a default explanation if LLM service is not available
        return "This is a default explanation for question #{$question->id}. In a production environment, this would be generated using our LLM service.";
    }
    
    public function updateUserProgress(User $user, int $topicId, bool $isCorrect, int $answerTime): void
    {
        $progress = UserProgress::firstOrCreate(
            ['user_id' => $user->id, 'topic_id' => $topicId],
            ['proficiency_level' => 0]
        );
        
        // Update proficiency based on answer correctness and time
        // Implementation of spaced repetition and knowledge decay algorithms
        $newProficiency = $this->calculateNewProficiency(
            $progress->proficiency_level,
            $isCorrect,
            $answerTime
        );
        
        $progress->proficiency_level = $newProficiency;
        $progress->last_interaction = now();
        $progress->save();
    }
    
    public function getPerformanceAnalysis(User $user, ?int $topicId = null, string $period = 'week'): array
    {
        // Get the user's performance data based on the specified period
        $query = UserProgress::where('user_id', $user->id);
        
        if ($topicId) {
            $query->where('topic_id', $topicId);
        }
        
        switch ($period) {
            case 'week':
                $query->where('last_interaction', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('last_interaction', '>=', now()->subMonth());
                break;
            // 'all' doesn't require a date filter
        }
        
        $progressData = $query->with('topic')->get();
        
        // Process data and return analysis
        return [
            'overall_proficiency' => $progressData->avg('proficiency_level') ?? 0,
            'topics' => $progressData->map(function($item) {
                return [
                    'topic_id' => $item->topic_id,
                    'topic_name' => $item->topic->name ?? 'Unknown Topic',
                    'proficiency' => $item->proficiency_level,
                    'last_studied' => $item->last_interaction
                ];
            }),
            'period' => $period
        ];
    }
    
    public function getRecommendations(User $user, ?int $topicId = null): array
    {
        // Get topics with lowest proficiency
        $weakTopicsQuery = UserProgress::where('user_id', $user->id)
                        ->orderBy('proficiency_level', 'asc');
        
        if ($topicId) {
            $weakTopicsQuery->where('topic_id', $topicId);
        }
        
        $weakTopics = $weakTopicsQuery->limit(3)->with('topic')->get();
        
        // Generate recommendations based on weak areas
        $recommendedTopics = $weakTopics->map(function($item) {
            return [
                'topic_id' => $item->topic_id,
                'topic_name' => $item->topic->name ?? 'Unknown Topic',
                'current_proficiency' => $item->proficiency_level,
                'recommended_questions' => rand(5, 10) // Placeholder
            ];
        });
        
        // Get additional recommended resources
        $recommendedResources = $this->getRecommendedResources($weakTopics);
        
        return [
            'weak_areas' => $recommendedTopics,
            'resources' => $recommendedResources,
            'next_review_time' => now()->addDays(1)->toDateTimeString()
        ];
    }
    
    // Helper methods
    protected function mapProficiencyToDifficulty(float $proficiency): string
    {
        // For debugging
        \Log::info("Mapping proficiency: {$proficiency}");
        
        if ($proficiency < 0.4) {
            return 'easy';
        } elseif ($proficiency < 0.7) {
            return 'medium';
        } else {
            return 'hard';
        }
    }
    
    protected function mapProficiencyToLevel(float $proficiency): string
    {
        if ($proficiency < 0.3) {
            return 'beginner';
        } elseif ($proficiency < 0.7) {
            return 'intermediate';
        } else {
            return 'advanced';
        }
    }
    
    protected function calculateNewProficiency(float $currentProficiency, bool $isCorrect, int $answerTime): float
    {
        // Implementation using spaced repetition algorithms
        $baseChange = $isCorrect ? 0.1 : -0.05;
        
        // Adjust based on answer time (quicker answers get more reward)
        $timeMultiplier = 1.0;
        if ($isCorrect) {
            if ($answerTime < 10) {
                $timeMultiplier = 1.5; // Fast and correct = bigger boost
            } elseif ($answerTime > 60) {
                $timeMultiplier = 0.8; // Slow but correct = smaller boost
            }
        }
        
        $newProficiency = $currentProficiency + ($baseChange * $timeMultiplier);
        
        // Keep proficiency between 0 and 1
        return max(0, min(1, $newProficiency));
    }
    
    protected function getUserAnswerContext(User $user, int $topicId): array
    {
        // Implementation to get relevant user answer history
        return [
            'recent_answered_questions' => $user->answers()
                ->whereHas('question', function ($query) use ($topicId) {
                    $query->where('topic_id', $topicId);
                })
                ->latest()
                ->limit(5)
                ->with('question')
                ->get()
                ->toArray(),
            'correct_percentage' => $user->answers()
                ->whereHas('question', function ($query) use ($topicId) {
                    $query->where('topic_id', $topicId);
                })
                ->where('is_correct', true)
                ->count() / max(1, $user->answers()
                    ->whereHas('question', function ($query) use ($topicId) {
                        $query->where('topic_id', $topicId);
                    })
                    ->count()) * 100
        ];
    }
    
    protected function getRecommendedResources($weakTopics): array
    {
        // Placeholder implementation to recommend resources
        return $weakTopics->map(function($item) {
            return [
                'topic_id' => $item->topic_id,
                'topic_name' => $item->topic->name ?? 'Unknown Topic',
                'resource_type' => array_rand(['video', 'article', 'exercise']),
                'title' => "Resource for " . ($item->topic->name ?? 'Topic'),
                'url' => "#" // Placeholder URL
            ];
        })->toArray();
    }
}
