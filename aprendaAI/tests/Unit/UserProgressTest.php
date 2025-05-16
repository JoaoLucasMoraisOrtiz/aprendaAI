<?php

namespace Tests\Unit;

use App\Models\Topic;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProgressTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_progress_can_be_created()
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        
        $userProgress = UserProgress::factory()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'proficiency_level' => 70,
            'last_activity_date' => now(),
            'mastery_status' => 'medium',
            'adaptive_recommendations' => json_encode(['next_steps' => 'Review chapter 3']),
        ]);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'proficiency_level' => 70,
            'mastery_status' => 'medium',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_progress_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $userProgress = UserProgress::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $userProgress->user);
        $this->assertEquals($user->id, $userProgress->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_progress_belongs_to_a_topic()
    {
        $topic = Topic::factory()->create();
        $userProgress = UserProgress::factory()->create(['topic_id' => $topic->id]);

        $this->assertInstanceOf(Topic::class, $userProgress->topic);
        $this->assertEquals($topic->id, $userProgress->topic->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_by_mastery_status()
    {
        // Criar progressos com diferentes status de domínio
        $easyProgress = UserProgress::factory()->create([
            'mastery_status' => 'easy'
        ]);
        
        $mediumProgress = UserProgress::factory()->create([
            'mastery_status' => 'medium'
        ]);
        
        $hardProgress = UserProgress::factory()->create([
            'mastery_status' => 'hard'
        ]);

        $easyProgresses = UserProgress::byMasteryStatus('easy')->get();
        $mediumProgresses = UserProgress::byMasteryStatus('medium')->get();
        $hardProgresses = UserProgress::byMasteryStatus('hard')->get();
        
        $this->assertEquals(1, $easyProgresses->count());
        $this->assertEquals(1, $mediumProgresses->count());
        $this->assertEquals(1, $hardProgresses->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_by_proficiency_range()
    {
        // Criar progressos com diferentes níveis de proficiência
        $lowProficiency = UserProgress::factory()->create([
            'proficiency_level' => 30
        ]);
        
        $mediumProficiency = UserProgress::factory()->create([
            'proficiency_level' => 60
        ]);
        
        $highProficiency = UserProgress::factory()->create([
            'proficiency_level' => 90
        ]);

        $lowProficiencies = UserProgress::byProficiencyRange(0, 40)->get();
        $mediumProficiencies = UserProgress::byProficiencyRange(40, 70)->get();
        $highProficiencies = UserProgress::byProficiencyRange(70, 100)->get();
        
        $this->assertEquals(1, $lowProficiencies->count());
        $this->assertEquals(1, $mediumProficiencies->count());
        $this->assertEquals(1, $highProficiencies->count());
        
        $this->assertEquals($lowProficiency->id, $lowProficiencies->first()->id);
        $this->assertEquals($mediumProficiency->id, $mediumProficiencies->first()->id);
        $this->assertEquals($highProficiency->id, $highProficiencies->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_get_recently_active_progress()
    {
        // Criar progresso com atividade recente
        $recentProgress = UserProgress::factory()->create([
            'last_activity_date' => now()->subDays(1)
        ]);
        
        // Criar progresso com atividade antiga
        $oldProgress = UserProgress::factory()->create([
            'last_activity_date' => now()->subDays(30)
        ]);

        $recentProgresses = UserProgress::recentlyActive(7)->get();
        
        $this->assertEquals(1, $recentProgresses->count());
        $this->assertEquals($recentProgress->id, $recentProgresses->first()->id);
    }
}
