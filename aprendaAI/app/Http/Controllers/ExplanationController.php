<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\GenerateExplanationRequest;
use App\Models\Question;
use App\Models\ExplanationCache;
use App\Services\LLM\LLMServiceInterface;
use Inertia\Inertia;

class ExplanationController extends Controller
{
    protected $llmService;
    
    public function __construct(LLMServiceInterface $llmService)
    {
        $this->llmService = $llmService;
        $this->middleware('auth');
    }
    
    /**
     * Display a listing of cached explanations.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $explanations = ExplanationCache::where('user_id', $user->id)
            ->with('question')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return Inertia::render('Explanations/Index', [
            'explanations' => $explanations
        ]);
    }

    /**
     * Generate a new personalized explanation for a question.
     */
    public function generate(GenerateExplanationRequest $request)
    {
        $user = $request->user();
        $questionId = $request->input('question_id');
        $question = Question::findOrFail($questionId);
        
        // Check if we have a cached explanation
        $cached = ExplanationCache::where('user_id', $user->id)
            ->where('question_id', $questionId)
            ->first();
            
        if ($cached && !$request->input('refresh', false)) {
            return response()->json([
                'explanation' => $cached->content,
                'cached' => true
            ]);
        }
        
        // Get user level from progress or set default
        $userLevel = $user->getTopicProficiencyLevel($question->topic_id) ?? 'beginner';
        
        // Generate explanation using LLM service
        $explanation = $this->llmService->generateExplanation(
            $question->content,
            $userLevel,
            [
                'topic_id' => $question->topic_id,
                'question_type' => $question->type,
                'difficulty' => $question->difficulty
            ]
        );
        
        // Cache the explanation
        if ($cached) {
            $cached->update(['content' => $explanation]);
        } else {
            ExplanationCache::create([
                'user_id' => $user->id,
                'question_id' => $questionId,
                'content' => $explanation
            ]);
        }
        
        return response()->json([
            'explanation' => $explanation,
            'cached' => false
        ]);
    }

    /**
     * Display the specified explanation.
     */
    public function show(string $id)
    {
        $explanation = ExplanationCache::with('question')->findOrFail($id);
        
        // Check if user has access to this explanation
        $this->authorize('view', $explanation);
        
        return Inertia::render('Explanations/Show', [
            'explanation' => $explanation
        ]);
    }

    /**
     * Rate an explanation's helpfulness.
     */
    public function rate(Request $request, string $id)
    {
        $explanation = ExplanationCache::findOrFail($id);
        
        // Check if user has access to this explanation
        $this->authorize('update', $explanation);
        
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500'
        ]);
        
        $explanation->update([
            'rating' => $request->input('rating'),
            'feedback' => $request->input('feedback')
        ]);
        
        return response()->json(['message' => 'Rating submitted successfully']);
    }

    /**
     * Delete a cached explanation.
     */
    public function destroy(string $id)
    {
        $explanation = ExplanationCache::findOrFail($id);
        
        // Check if user has access to delete this explanation
        $this->authorize('delete', $explanation);
        
        $explanation->delete();
        
        return response()->json(['message' => 'Explanation deleted successfully']);
    }
}
