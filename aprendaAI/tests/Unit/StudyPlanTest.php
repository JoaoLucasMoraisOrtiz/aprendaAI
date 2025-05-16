<?php

namespace Tests\Unit;

use App\Models\StudyPlan;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyPlanTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_study_plan_can_be_created()
    {
        $user = User::factory()->create();
        
        $studyPlan = StudyPlan::factory()->create([
            'user_id' => $user->id,
            'title' => 'Plano de Estudos para Vestibular',
            'description' => 'Plano para preparação para o vestibular',
            'start_date' => '2023-06-01',
            'end_date' => '2023-12-31',
            'goal' => 'Aprovação no vestibular',
            'is_adaptive' => true,
        ]);

        $this->assertDatabaseHas('study_plans', [
            'title' => 'Plano de Estudos para Vestibular',
            'description' => 'Plano para preparação para o vestibular',
            'goal' => 'Aprovação no vestibular',
            'is_adaptive' => true,
        ]);
    }

    /** @test */
    public function a_study_plan_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $studyPlan = StudyPlan::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $studyPlan->user);
        $this->assertEquals($user->id, $studyPlan->user->id);
    }

    /** @test */
    public function a_study_plan_has_many_study_sessions()
    {
        $studyPlan = StudyPlan::factory()->create();
        $studySession = StudySession::factory()->create(['study_plan_id' => $studyPlan->id]);

        $this->assertInstanceOf(StudySession::class, $studyPlan->sessions->first());
        $this->assertEquals(1, $studyPlan->sessions->count());
    }

    /** @test */
    public function it_can_filter_active_study_plans()
    {
        // Criar um plano de estudos ativo
        $activeStudyPlan = StudyPlan::factory()->create([
            'status' => 'active'
        ]);
        
        // Criar um plano de estudos inativo
        $inactiveStudyPlan = StudyPlan::factory()->create([
            'status' => 'completed'
        ]);

        $activeStudyPlans = StudyPlan::where('status', 'active')->get();
        
        $this->assertEquals(1, $activeStudyPlans->count());
        $this->assertEquals($activeStudyPlan->id, $activeStudyPlans->first()->id);
    }

    /** @test */
    public function it_can_filter_adaptive_study_plans()
    {
        // Criar um plano de estudos adaptativo
        $adaptiveStudyPlan = StudyPlan::factory()->create([
            'is_adaptive' => true
        ]);
        
        // Criar um plano de estudos não adaptativo
        $nonAdaptiveStudyPlan = StudyPlan::factory()->create([
            'is_adaptive' => false
        ]);

        $adaptiveStudyPlans = StudyPlan::adaptive()->get();
        
        $this->assertEquals(1, $adaptiveStudyPlans->count());
        $this->assertEquals($adaptiveStudyPlan->id, $adaptiveStudyPlans->first()->id);
    }

    /** @test */
    public function it_can_calculate_progress_percentage()
    {
        $studyPlan = StudyPlan::factory()->create();
        
        // Criar 4 sessões de estudo, 2 completas e 2 incompletas
        StudySession::factory()->create([
            'study_plan_id' => $studyPlan->id,
            'is_completed' => true
        ]);
        
        StudySession::factory()->create([
            'study_plan_id' => $studyPlan->id,
            'is_completed' => true
        ]);
        
        StudySession::factory()->create([
            'study_plan_id' => $studyPlan->id,
            'is_completed' => false
        ]);
        
        StudySession::factory()->create([
            'study_plan_id' => $studyPlan->id,
            'is_completed' => false
        ]);

        $this->assertEquals(50, $studyPlan->progressPercentage());
    }
}
