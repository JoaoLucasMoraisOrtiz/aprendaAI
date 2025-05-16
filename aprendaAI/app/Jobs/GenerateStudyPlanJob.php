<?php

namespace App\Jobs;

use App\Models\LLMInteraction;
use App\Models\StudyPlan;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\LLM\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateStudyPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The user for whom the study plan is being generated.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The list of subjects to include in the study plan.
     *
     * @var array
     */
    protected $subjects;

    /**
     * The duration of the study plan in weeks.
     *
     * @var int
     */
    protected $duration;

    /**
     * The goal of the study plan.
     *
     * @var string
     */
    protected $goal;

    /**
     * Additional preferences for the study plan.
     *
     * @var array|null
     */
    protected $preferences;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $subjects, int $duration, string $goal, array $preferences = [])
    {
        $this->user = $user;
        $this->subjects = $subjects;
        $this->duration = $duration;
        $this->goal = $goal;
        $this->preferences = $preferences;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService): void
    {
        Log::info('Starting study plan generation for user: ' . $this->user->id);
        
        // Prepare prompt for LLM
        $prompt = $this->buildPrompt();
        
        // Send request to Gemini
        $response = $geminiService->generate($prompt, 'pt-BR');
        
        // Record the interaction with LLM
        $interaction = LLMInteraction::create([
            'user_id' => $this->user->id,
            'interaction_type' => 'study_plan',
            'prompt' => $prompt,
            'response' => $response['content'] ?? null,
            'model_used' => 'gemini-pro',
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            'status' => $response['success'] ? 'success' : 'failed',
            'metadata' => [
                'subjects' => $this->subjects,
                'duration' => $this->duration,
                'preferences' => $this->preferences
            ]
        ]);
        
        // If response was not successful, log error and return
        if (!($response['success'] ?? false)) {
            Log::error('Failed to generate study plan: ' . ($response['error'] ?? 'Unknown error'));
            return;
        }
        
        // Parse the response content
        try {
            $planData = json_decode($response['content'], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }
            
            // Create the study plan
            $studyPlan = StudyPlan::create([
                'user_id' => $this->user->id,
                'title' => $planData['title'] ?? ('Plano de estudos - ' . Carbon::now()->format('d/m/Y')),
                'description' => $planData['description'] ?? null,
                'goal' => $this->goal,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addWeeks($this->duration),
                'status' => 'active',
                'is_adaptive' => true,
                'metadata' => [
                    'recommendations' => $planData['recommendations'] ?? []
                ]
            ]);
            
            Log::info('Created study plan with id: ' . $studyPlan->id);
            
            // Create study sessions
            $this->createStudySessions($studyPlan, $planData);
            
            Log::info('Study plan generation completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Error processing study plan data: ' . $e->getMessage());
            $interaction->update(['status' => 'failed']);
        }
    }
    
    /**
     * Build the prompt for the LLM.
     */
    protected function buildPrompt(): string
    {
        $subjectsText = collect($this->subjects)
            ->map(fn($subject) => "- {$subject['name']} (Nível: {$subject['level']})")
            ->join("\n");
            
        $preferencesText = '';
        if (!empty($this->preferences)) {
            $preferencesText = "Preferências adicionais:\n" . collect($this->preferences)
                ->map(fn($value, $key) => "- $key: $value")
                ->join("\n");
        }
        
        return <<<PROMPT
        Crie um plano de estudos detalhado para um estudante com o seguinte objetivo: {$this->goal}.
        
        O plano deve durar {$this->duration} semanas e incluir os seguintes assuntos:
        {$subjectsText}
        
        {$preferencesText}
        
        Retorne o plano no formato JSON com a seguinte estrutura:
        {
          "title": "Título do plano de estudos",
          "description": "Descrição breve",
          "duration_weeks": número de semanas,
          "goal": "objetivo do plano",
          "weekly_schedule": [
            {
              "week": número da semana,
              "focus": "foco principal da semana",
              "sessions": [
                {
                  "day": "dia da semana em inglês",
                  "duration_minutes": duração em minutos,
                  "subject": "nome da matéria",
                  "topic": "tópico específico",
                  "resources": ["recurso 1", "recurso 2"],
                  "activities": ["atividade 1", "atividade 2"]
                }
              ]
            }
          ],
          "recommendations": ["recomendação 1", "recomendação 2"]
        }
        PROMPT;
    }
    
    /**
     * Create study sessions from the plan data.
     */
    protected function createStudySessions(StudyPlan $studyPlan, array $planData): void
    {
        $startDate = Carbon::now();
        
        // Loop through each week in the schedule
        foreach ($planData['weekly_schedule'] ?? [] as $week) {
            $weekNumber = $week['week'] ?? 1;
            
            // Loop through each session in the week
            foreach ($week['sessions'] ?? [] as $session) {
                $dayOfWeek = $session['day'] ?? 'Monday';
                $weekStartDate = (clone $startDate)->addWeeks($weekNumber - 1);
                
                // Calculate the date based on day of week
                $sessionDate = $this->getDateFromWeekDay($weekStartDate, $dayOfWeek);
                
                // Create the study session
                StudySession::create([
                    'study_plan_id' => $studyPlan->id,
                    'topic_id' => $this->getTopicId($session['subject'] ?? '', $session['topic'] ?? ''),
                    'duration' => $session['duration_minutes'] ?? 60,
                    'scheduled_date' => $sessionDate,
                    'status' => 'pending',
                    'is_completed' => false,
                    'notes' => implode(', ', $session['activities'] ?? []),
                    'resources' => json_encode($session['resources'] ?? [])
                ]);
            }
        }
    }
    
    /**
     * Get a Carbon date from a week start date and day name.
     */
    protected function getDateFromWeekDay(Carbon $weekStart, string $dayName): Carbon
    {
        $days = [
            'Monday' => 0,
            'Tuesday' => 1,
            'Wednesday' => 2,
            'Thursday' => 3,
            'Friday' => 4,
            'Saturday' => 5,
            'Sunday' => 6
        ];
        
        $dayOffset = $days[ucfirst($dayName)] ?? 0;
        return (clone $weekStart)->addDays($dayOffset);
    }
    
    /**
     * Find or create a topic id based on subject and topic names.
     */
    protected function getTopicId(string $subjectName, string $topicName): int
    {
        // Simplistic approach: find first topic that matches the name
        // In a real application, this would need more sophisticated matching
        $topic = Topic::where('name', 'like', "%{$topicName}%")->first();
        
        if (!$topic) {
            // Find or create a subject
            $subject = Subject::firstOrCreate(
                ['name' => $subjectName],
                ['description' => "Auto-created subject for {$subjectName}", 'is_active' => true]
            );
            
            // Create a topic if not found
            $topic = Topic::create([
                'subject_id' => $subject->id,
                'name' => $topicName,
                'description' => "Auto-created topic for {$topicName}",
                'is_active' => true,
                'difficulty_level' => 'medium',
                'order' => 1,
                'estimated_time_minutes' => 60,
                'prerequisites' => json_encode([])
            ]);
        }
        
        return $topic->id;
    }
}
