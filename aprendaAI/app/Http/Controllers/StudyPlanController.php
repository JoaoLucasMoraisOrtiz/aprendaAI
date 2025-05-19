<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateAdaptiveStudyPlanRequest;
use App\Models\StudyPlan;
use App\Models\StudySession;
use App\Models\Topic;
use App\Models\Subject;
use App\Models\UserProgress;
use App\Services\LLM\LLMServiceInterface;
use App\Services\Learning\AdaptiveLearningService;
use App\Jobs\GenerateStudyPlanJob;
use Inertia\Inertia;

class StudyPlanController extends Controller
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
     * Display a listing of the user's study plans.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $plans = StudyPlan::where('user_id', $user->id)
            ->with(['sessions' => function($query) {
                $query->orderBy('scheduled_date', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return Inertia::render('StudyPlans/Index', [
            'plans' => $plans
        ]);
    }

    /**
     * Show the form for creating a new study plan.
     */
    public function create()
    {
        $subjects = Subject::with('topics')->get();
        
        return Inertia::render('StudyPlans/Create', [
            'subjects' => $subjects
        ]);
    }

    /**
     * Store a newly created study plan in storage.
     */
    public function store(CreateAdaptiveStudyPlanRequest $request)
    {
        $user = $request->user();
        
        $plan = StudyPlan::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ]);
        
        // Submit job to generate study plan asynchronously
        GenerateStudyPlanJob::dispatch(
            $plan,
            $request->input('topics'),
            $request->input('preferences', [])
        );
        
        return redirect()->route('study-plans.show', $plan)
            ->with('message', 'Seu plano de estudos está sendo gerado e estará disponível em breve.');
    }

    /**
     * Generate an adaptive study plan based on user performance.
     */
    public function generateAdaptive(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,id',
            'hours_per_week' => 'required|integer|min:1|max:168'
        ]);
        
        // Create a new study plan
        $plan = StudyPlan::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ]);
        
        // Get user progress data for selected subjects
        $subjects = Subject::whereIn('id', $request->input('subjects'))->with('topics')->get();
        $topicIds = $subjects->pluck('topics')->flatten()->pluck('id')->toArray();
        
        $userProgress = UserProgress::where('user_id', $user->id)
            ->whereIn('topic_id', $topicIds)
            ->get();
            
        // Submit job to generate adaptive study plan
        GenerateStudyPlanJob::dispatch(
            $plan,
            $topicIds,
            [
                'hours_per_week' => $request->input('hours_per_week'),
                'user_progress' => $userProgress
            ]
        );
        
        return redirect()->route('study-plans.show', $plan)
            ->with('message', 'Seu plano de estudos adaptativo está sendo gerado e estará disponível em breve.');
    }

    /**
     * Display the specified study plan.
     */
    public function show(string $id)
    {
        $plan = StudyPlan::with(['sessions' => function($query) {
                $query->with('topic')->orderBy('scheduled_date', 'asc');
            }])
            ->findOrFail($id);
            
        // Check if user has access to this plan
        $this->authorize('view', $plan);
        
        return Inertia::render('StudyPlans/Show', [
            'plan' => $plan
        ]);
    }

    /**
     * Show the form for editing the specified study plan.
     */
    public function edit(string $id)
    {
        $plan = StudyPlan::with('sessions.topic')->findOrFail($id);
        
        // Check if user has access to edit this plan
        $this->authorize('update', $plan);
        
        $subjects = Subject::with('topics')->get();
        
        return Inertia::render('StudyPlans/Edit', [
            'plan' => $plan,
            'subjects' => $subjects
        ]);
    }

    /**
     * Update the specified study plan in storage.
     */
    public function update(Request $request, string $id)
    {
        $plan = StudyPlan::findOrFail($id);
        
        // Check if user has access to update this plan
        $this->authorize('update', $plan);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);
        
        $plan->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ]);
        
        // If sessions are provided, update them
        if ($request->has('sessions')) {
            foreach ($request->input('sessions') as $sessionData) {
                $session = StudySession::findOrFail($sessionData['id']);
                
                if ($session->study_plan_id == $plan->id) {
                    $session->update([
                        'scheduled_date' => $sessionData['scheduled_date'],
                        'duration' => $sessionData['duration'],
                        'status' => $sessionData['status']
                    ]);
                }
            }
        }
        
        return redirect()->route('study-plans.show', $plan)
            ->with('message', 'Plano de estudos atualizado com sucesso.');
    }

    /**
     * Remove the specified study plan from storage.
     */
    public function destroy(string $id)
    {
        $plan = StudyPlan::findOrFail($id);
        
        // Check if user has access to delete this plan
        $this->authorize('delete', $plan);
        
        // Delete associated sessions
        StudySession::where('study_plan_id', $plan->id)->delete();
        
        // Delete the plan
        $plan->delete();
        
        return redirect()->route('study-plans.index')
            ->with('message', 'Plano de estudos excluído com sucesso.');
    }
    
    /**
     * Mark a study session as completed.
     */
    public function completeSession(string $planId, string $sessionId)
    {
        $plan = StudyPlan::findOrFail($planId);
        $session = StudySession::findOrFail($sessionId);
        
        // Check if session belongs to this plan and user has access
        if ($session->study_plan_id != $plan->id) {
            abort(404);
        }
        
        $this->authorize('update', $plan);
        
        $session->update([
            'status' => 'completed'
        ]);
        
        return response()->json(['message' => 'Sessão marcada como concluída']);
    }
    
    /**
     * Reschedule a study session.
     */
    public function rescheduleSession(Request $request, string $planId, string $sessionId)
    {
        $plan = StudyPlan::findOrFail($planId);
        $session = StudySession::findOrFail($sessionId);
        
        // Check if session belongs to this plan and user has access
        if ($session->study_plan_id != $plan->id) {
            abort(404);
        }
        
        $this->authorize('update', $plan);
        
        $request->validate([
            'scheduled_date' => 'required|date',
            'duration' => 'required|integer|min:5'
        ]);
        
        $session->update([
            'scheduled_date' => $request->input('scheduled_date'),
            'duration' => $request->input('duration'),
            'status' => 'pending'
        ]);
        
        return response()->json(['message' => 'Sessão reagendada com sucesso']);
    }
}
