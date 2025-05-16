<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AnalyzePerformanceRequest;
use App\Models\LearningInsight;
use App\Models\UserProgress;
use App\Models\Topic;
use App\Services\LLM\LLMServiceInterface;
use App\Services\Learning\AdaptiveLearningService;
use Inertia\Inertia;

class LearningInsightsController extends Controller
{
    protected $llmService;
    protected $adaptiveLearningService;
    
    public function __construct(LLMServiceInterface $llmService, AdaptiveLearningService $adaptiveLearningService)
    {
        $this->llmService = $llmService;
        $this->adaptiveLearningService = $adaptiveLearningService;
        $this->middleware('auth');
    }

    /**
     * Display a learning dashboard with insights.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's progress across topics
        $progress = UserProgress::where('user_id', $user->id)
            ->with('topic')
            ->get();
            
        // Get recent insights
        $insights = LearningInsight::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Get summary stats
        $stats = $this->getPerformanceStats($user->id);
        
        return Inertia::render('Learning/Insights', [
            'progress' => $progress,
            'insights' => $insights,
            'stats' => $stats
        ]);
    }

    /**
     * Generate insights for a specific topic.
     */
    public function generateInsights(AnalyzePerformanceRequest $request)
    {
        $user = $request->user();
        $topicId = $request->input('topic_id');
        $period = $request->input('period', 'month');
        
        $topic = Topic::findOrFail($topicId);
        
        // Get user's answers and progress data
        $userAnswers = $this->adaptiveLearningService->getUserAnswerHistory($user, $topicId, $period);
        $userProgress = UserProgress::where('user_id', $user->id)
            ->where('topic_id', $topicId)
            ->first();
        
        if (empty($userAnswers)) {
            return response()->json([
                'message' => 'Não há dados suficientes para gerar insights para este tópico.'
            ], 404);
        }
        
        // Generate insights using LLM
        $analysisResult = $this->llmService->analyzePerformance($userAnswers, [
            'proficiency_level' => $userProgress ? $userProgress->proficiency_level : 0,
            'topic_name' => $topic->name
        ]);
        
        // Store insights
        $insight = LearningInsight::create([
            'user_id' => $user->id,
            'topic_id' => $topicId,
            'content' => $analysisResult['content'],
            'strengths' => $analysisResult['strengths'],
            'weaknesses' => $analysisResult['weaknesses'],
            'recommendations' => $analysisResult['recommendations'],
            'metadata' => [
                'period' => $period,
                'question_count' => count($userAnswers),
                'correct_percentage' => $analysisResult['correct_percentage'] ?? null
            ]
        ]);
        
        return response()->json([
            'insight' => $insight,
            'message' => 'Insights gerados com sucesso'
        ]);
    }

    /**
     * Show a specific learning insight.
     */
    public function show(string $id)
    {
        $insight = LearningInsight::with('topic')->findOrFail($id);
        
        // Check if user has access to this insight
        $this->authorize('view', $insight);
        
        return Inertia::render('Learning/ShowInsight', [
            'insight' => $insight
        ]);
    }

    /**
     * Get topic-specific performance data.
     */
    public function topicPerformance(Request $request, string $topicId)
    {
        $user = $request->user();
        $topic = Topic::findOrFail($topicId);
        
        $performance = $this->adaptiveLearningService->getTopicPerformanceData($user, $topicId);
        
        return response()->json([
            'topic' => $topic,
            'performance' => $performance
        ]);
    }

    /**
     * Get overall performance stats.
     */
    protected function getPerformanceStats(int $userId)
    {
        // Implementation of gathering overall performance statistics
        $totalQuestions = \App\Models\UserAnswer::where('user_id', $userId)->count();
        $correctAnswers = \App\Models\UserAnswer::where('user_id', $userId)
            ->where('is_correct', true)
            ->count();
            
        $averageTime = \App\Models\UserAnswer::where('user_id', $userId)
            ->avg('answer_time');
            
        $mastery = UserProgress::where('user_id', $userId)
            ->where('proficiency_level', '>=', 0.8)
            ->count();
            
        return [
            'total_questions' => $totalQuestions,
            'correct_percentage' => $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0,
            'average_time' => $averageTime ?? 0,
            'topics_mastered' => $mastery
        ];
    }
}
