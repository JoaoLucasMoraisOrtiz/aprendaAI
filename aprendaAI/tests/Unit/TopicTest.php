<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Question;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_topic_can_be_created()
    {
        $subject = Subject::factory()->create();
        
        $topic = Topic::factory()->create([
            'subject_id' => $subject->id,
            'name' => 'Álgebra Linear',
            'description' => 'Estudo de vetores e matrizes',
            'difficulty_level' => 'medium',
        ]);

        $this->assertDatabaseHas('topics', [
            'name' => 'Álgebra Linear',
            'description' => 'Estudo de vetores e matrizes',
            'difficulty_level' => 'medium',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_topic_belongs_to_a_subject()
    {
        $subject = Subject::factory()->create();
        $topic = Topic::factory()->create(['subject_id' => $subject->id]);

        $this->assertInstanceOf(Subject::class, $topic->subject);
        $this->assertEquals($subject->id, $topic->subject->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_topic_has_many_questions()
    {
        $topic = Topic::factory()->create();
        $question = Question::factory()->create(['topic_id' => $topic->id]);

        $this->assertInstanceOf(Question::class, $topic->questions->first());
        $this->assertEquals(1, $topic->questions->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_topic_has_user_progress()
    {
        $topic = Topic::factory()->create();
        $userProgress = UserProgress::factory()->create(['topic_id' => $topic->id]);

        $this->assertInstanceOf(UserProgress::class, $topic->userProgress->first());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_active_topics()
    {
        // Criar um tópico ativo
        $activeTopic = Topic::factory()->create([
            'is_active' => true
        ]);
        
        // Criar um tópico inativo
        $inactiveTopic = Topic::factory()->create([
            'is_active' => false
        ]);

        $activeTopics = Topic::active()->get();
        
        $this->assertEquals(1, $activeTopics->count());
        $this->assertEquals($activeTopic->id, $activeTopics->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_topics_by_difficulty_level()
    {
        // Criar tópicos com diferentes níveis de dificuldade
        $easyTopic = Topic::factory()->create([
            'difficulty_level' => 'easy'
        ]);
        
        $mediumTopic = Topic::factory()->create([
            'difficulty_level' => 'medium'
        ]);
        
        $hardTopic = Topic::factory()->create([
            'difficulty_level' => 'hard'
        ]);

        $easyTopics = Topic::byDifficulty('easy')->get();
        $mediumTopics = Topic::byDifficulty('medium')->get();
        $hardTopics = Topic::byDifficulty('hard')->get();
        
        $this->assertEquals(1, $easyTopics->count());
        $this->assertEquals(1, $mediumTopics->count());
        $this->assertEquals(1, $hardTopics->count());
        
        $this->assertEquals($easyTopic->id, $easyTopics->first()->id);
        $this->assertEquals($mediumTopic->id, $mediumTopics->first()->id);
        $this->assertEquals($hardTopic->id, $hardTopics->first()->id);
    }
}
