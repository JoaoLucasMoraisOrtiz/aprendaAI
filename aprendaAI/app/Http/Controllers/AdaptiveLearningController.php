<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\GenerateExplanationRequest;
use App\Services\Learning\AdaptiveLearningService;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Answer;
use App\Models\ExplanationCache;
use App\Models\LearningInsight;
use Inertia\Inertia;

class AdaptiveLearningController extends Controller
{
    protected $adaptiveService;
    
    public function __construct(AdaptiveLearningService $adaptiveService)
    {
        $this->adaptiveService = $adaptiveService;
        // $this->middleware('auth'); // Removed for API routes using Sanctum
    }
    
    public function index()
    {
        $topics = Topic::where('is_active', true)->get();
        
        return response()->json(['data' => $topics]);
    }
    
    public function getQuestions(Request $request, $topicId)
    {
        $user = $request->user();
        $count = $request->input('count', 5);
        
        $questions = $this->adaptiveService->getNextQuestions($user, $topicId, $count);
        
        // Log questions count for debugging
        \Log::info('Questions count: ' . count($questions));
        \Log::info('Questions: ' . json_encode($questions));
        
        return response()->json(['data' => $questions]);
    }
    
    public function submitAnswer(Request $request)
    {
        $user = $request->user();
        $questionId = $request->input('question_id');
        $answerId = $request->input('answer_id');
        $timeSpent = $request->input('time_spent');
        
        $question = Question::findOrFail($questionId);
        $isCorrect = $this->checkAnswer($question, $answerId);
        
        // Record user's answer
        $userAnswer = $user->answers()->create([
            'question_id' => $questionId,
            'answer_id' => $answerId,
            'is_correct' => $isCorrect,
            'time_spent' => $timeSpent
        ]);
        
        // Update user progress
        $this->adaptiveService->updateUserProgress($user, $question->topic_id, $isCorrect, $timeSpent);
        
        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'message' => $isCorrect ? 'Correct!' : 'Incorrect!'
        ]);
    }
    
    public function getExplanation(Request $request, $questionId)
    {
        $user = $request->user();
        $question = Question::findOrFail($questionId);
        
        // Check if we have a cached explanation
        $cachedExplanation = ExplanationCache::where('question_id', $questionId)
            ->first();
            
        if (!$cachedExplanation) {
            // Generate a new explanation
            $explanation = $this->adaptiveService->generatePersonalizedExplanation($user, $question);
            
            // Cache the explanation
            $cachedExplanation = ExplanationCache::create([
                'question_id' => $questionId,
                'explanation' => $explanation,
                'difficulty_level' => $question->difficulty_level,
                'is_personalized' => true
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'explanation' => $cachedExplanation->explanation,
                'is_personalized' => $cachedExplanation->is_personalized
            ]
        ]);
    }
    
    public function getPerformanceAnalysis(Request $request)
    {
        $user = $request->user();
        $topicId = $request->input('topic_id');
        $period = $request->input('period', 'week'); // week, month, all
        
        $analysis = $this->adaptiveService->getPerformanceAnalysis($user, $topicId, $period);
        
        return response()->json([
            'success' => true,
            'data' => [
                'topics' => $analysis['topics'],
                'overall_performance' => $analysis['overall_proficiency'],
                'strengths' => [],
                'weaknesses' => [],
                'recommendations' => []
            ]
        ]);
    }
    
    public function recommendNextSteps(Request $request)
    {
        $user = $request->user();
        $topicId = $request->input('topic_id');
        
        $recommendations = $this->adaptiveService->getRecommendations($user, $topicId);
        
        // Create a learning insight based on recommendations
        LearningInsight::create([
            'user_id' => $user->id,
            'insight_type' => 'recommendation',
            'data' => [
                'recommendations' => $recommendations,
                'timestamp' => now()->toIso8601String()
            ],
            'generated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'topics' => $recommendations['weak_areas'],
                'questions' => [],
                'resources' => $recommendations['resources']
            ]
        ]);
    }
    
    protected function checkAnswer(Question $question, $answerId)
    {
        if ($question->type === 'multiple_choice' || $question->type === 'true_false') {
            $selectedAnswer = Answer::where('question_id', $question->id)
                ->where('id', $answerId)
                ->first();
            // Use the actual is_correct column from the Answer model
            return $selectedAnswer && $selectedAnswer->is_correct === true;
        } elseif ($question->type === 'essay') {
            // Essay questions are manually graded, so we return false (not correct by default)
            return false;
        }
        return false;
    }
}
