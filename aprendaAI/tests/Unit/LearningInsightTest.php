<?php

namespace Tests\Unit;

use App\Models\LearningInsight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LearningInsightTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_learning_insight()
    {
        $user = User::factory()->create();
        
        $data = [
            'strengths' => ['Mathematics', 'Physics'],
            'weaknesses' => ['Chemistry'],
            'recommendations' => 'Focus more on organic chemistry.'
        ];
        
        $insight = LearningInsight::create([
            'user_id' => $user->id,
            'insight_type' => 'performance_analysis',
            'data' => $data,
            'generated_at' => now(),
        ]);
        
        $this->assertDatabaseHas('learning_insights', [
            'user_id' => $user->id,
            'insight_type' => 'performance_analysis',
        ]);
        
        $this->assertEquals($data, $insight->data);
        $this->assertEquals('performance_analysis', $insight->insight_type);
    }
    
    #[Test]
    public function it_belongs_to_user()
    {
        $user = User::factory()->create();
        
        $insight = LearningInsight::factory()->create([
            'user_id' => $user->id
        ]);
        
        $this->assertInstanceOf(User::class, $insight->user);
        $this->assertEquals($user->id, $insight->user->id);
    }
    
    #[Test]
    public function a_user_can_have_multiple_learning_insights()
    {
        $user = User::factory()->create();
        
        $strengths = LearningInsight::factory()->create([
            'user_id' => $user->id,
            'insight_type' => 'strengths'
        ]);
        
        $weaknesses = LearningInsight::factory()->create([
            'user_id' => $user->id,
            'insight_type' => 'weaknesses'
        ]);
        
        $progress = LearningInsight::factory()->create([
            'user_id' => $user->id,
            'insight_type' => 'progress'
        ]);
        
        $this->assertCount(3, $user->learningInsights);
        $this->assertTrue($user->learningInsights->contains($strengths));
        $this->assertTrue($user->learningInsights->contains($weaknesses));
        $this->assertTrue($user->learningInsights->contains($progress));
    }
}
