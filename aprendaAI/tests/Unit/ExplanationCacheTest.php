<?php

namespace Tests\Unit;

use App\Models\ExplanationCache;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExplanationCacheTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_explanation_cache()
    {
        // Criar uma questão
        $question = Question::factory()->create([
            'content' => 'Qual é a integral de x² dx?',
            'difficulty_level' => 'medium'
        ]);

        // Criar um cache de explicação
        $cache = ExplanationCache::create([
            'question_id' => $question->id,
            'explanation' => 'A integral de x² dx é x³/3 + C',
            'difficulty_level' => 'easy',
            'is_personalized' => true
        ]);

        // Verificar se o cache foi criado
        $this->assertDatabaseHas('explanation_cache', [
            'question_id' => $question->id,
            'difficulty_level' => 'easy',
            'is_personalized' => true
        ]);

        // Verificar se o conteúdo está correto
        $this->assertEquals('A integral de x² dx é x³/3 + C', $cache->explanation);
    }

    #[Test]
    public function it_belongs_to_question()
    {
        // Create a question
        $question = Question::factory()->create();

        // Create explanation cache for the question
        $cache = ExplanationCache::factory()->create([
            'question_id' => $question->id
        ]);

        // Test the relationship
        $this->assertInstanceOf(Question::class, $cache->question);
        $this->assertEquals($question->id, $cache->question->id);
    }

    #[Test]
    public function a_question_can_have_multiple_explanation_caches()
    {
        // Create a question
        $question = Question::factory()->create();

        // Create multiple explanation caches with different difficulty levels
        $easyCache = ExplanationCache::factory()->create([
            'question_id' => $question->id,
            'difficulty_level' => 'easy'
        ]);

        $mediumCache = ExplanationCache::factory()->create([
            'question_id' => $question->id,
            'difficulty_level' => 'medium'
        ]);

        $hardCache = ExplanationCache::factory()->create([
            'question_id' => $question->id,
            'difficulty_level' => 'hard'
        ]);

        // Test the relationship
        $this->assertCount(3, $question->explanationCaches);
        $this->assertTrue($question->explanationCaches->contains($easyCache));
        $this->assertTrue($question->explanationCaches->contains($mediumCache));
        $this->assertTrue($question->explanationCaches->contains($hardCache));
    }
}
