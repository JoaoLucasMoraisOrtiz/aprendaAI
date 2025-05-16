<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_subject_can_be_created()
    {
        $subject = Subject::factory()->create([
            'name' => 'Matemática',
            'description' => 'Estudo de números, quantidades, espaço, estrutura e mudança',
        ]);

        $this->assertDatabaseHas('subjects', [
            'name' => 'Matemática',
            'description' => 'Estudo de números, quantidades, espaço, estrutura e mudança',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_subject_has_many_topics()
    {
        $subject = Subject::factory()->create();
        $topic = Topic::factory()->create(['subject_id' => $subject->id]);

        $this->assertInstanceOf(Topic::class, $subject->topics->first());
        $this->assertEquals(1, $subject->topics->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_subject_can_get_active_topics()
    {
        $subject = Subject::factory()->create();
        
        // Criar um tópico ativo
        Topic::factory()->create([
            'subject_id' => $subject->id,
            'is_active' => true
        ]);
        
        // Criar um tópico inativo
        Topic::factory()->create([
            'subject_id' => $subject->id,
            'is_active' => false
        ]);

        $this->assertEquals(1, $subject->activeTopics()->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_subject_can_get_topics_by_difficulty()
    {
        $subject = Subject::factory()->create();
        
        // Criar tópicos com diferentes níveis de dificuldade
        Topic::factory()->create([
            'subject_id' => $subject->id,
            'difficulty_level' => 'easy'
        ]);
        
        Topic::factory()->create([
            'subject_id' => $subject->id,
            'difficulty_level' => 'medium'
        ]);
        
        Topic::factory()->create([
            'subject_id' => $subject->id,
            'difficulty_level' => 'hard'
        ]);

        $this->assertEquals(1, $subject->getTopicsByDifficulty('easy')->count());
        $this->assertEquals(1, $subject->getTopicsByDifficulty('medium')->count());
        $this->assertEquals(1, $subject->getTopicsByDifficulty('hard')->count());
    }
}
