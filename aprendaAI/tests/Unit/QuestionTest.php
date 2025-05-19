<?php

namespace Tests\Unit;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Topic;
use App\Models\UserAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_question_can_be_created()
    {
        $topic = Topic::factory()->create();
        
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'content' => 'Qual é a fórmula da área de um círculo?',
            'difficulty_level' => 'medium',
            'type' => 'multiple_choice',
        ]);

        $this->assertDatabaseHas('questions', [
            'content' => 'Qual é a fórmula da área de um círculo?',
            'difficulty_level' => 'medium',
            'type' => 'multiple_choice',
        ]);
    }

    #[Test]
    public function a_question_belongs_to_a_topic()
    {
        $topic = Topic::factory()->create();
        $question = Question::factory()->create(['topic_id' => $topic->id]);

        $this->assertInstanceOf(Topic::class, $question->topic);
        $this->assertEquals($topic->id, $question->topic->id);
    }

    #[Test]
    public function a_question_has_many_answers()
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $this->assertInstanceOf(Answer::class, $question->answers->first());
        $this->assertEquals(1, $question->answers->count());
    }

    #[Test]
    public function a_question_has_user_answers()
    {
        $question = Question::factory()->create();
        $userAnswer = UserAnswer::factory()->create(['question_id' => $question->id]);

        $this->assertInstanceOf(UserAnswer::class, $question->userAnswers->first());
    }

    #[Test]
    public function it_can_get_correct_answers()
    {
        $question = Question::factory()->create();
        
        // Criar respostas corretas e incorretas
        $correctAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true
        ]);
        
        $incorrectAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false
        ]);

        $correctAnswers = $question->correctAnswers();
        
        $this->assertEquals(1, $correctAnswers->count());
        $this->assertEquals($correctAnswer->id, $correctAnswers->first()->id);
    }

    #[Test]
    public function it_can_filter_by_difficulty_level()
    {
        // Criar questões com diferentes níveis de dificuldade
        $easyQuestion = Question::factory()->create([
            'difficulty_level' => 'easy'
        ]);
        
        $mediumQuestion = Question::factory()->create([
            'difficulty_level' => 'medium'
        ]);
        
        $hardQuestion = Question::factory()->create([
            'difficulty_level' => 'hard'
        ]);

        $easyQuestions = Question::byDifficulty('easy')->get();
        $mediumQuestions = Question::byDifficulty('medium')->get();
        $hardQuestions = Question::byDifficulty('hard')->get();
        
        $this->assertEquals(1, $easyQuestions->count());
        $this->assertEquals(1, $mediumQuestions->count());
        $this->assertEquals(1, $hardQuestions->count());
        
        $this->assertEquals($easyQuestion->id, $easyQuestions->first()->id);
        $this->assertEquals($mediumQuestion->id, $mediumQuestions->first()->id);
        $this->assertEquals($hardQuestion->id, $hardQuestions->first()->id);
    }

    #[Test]
    public function it_can_filter_by_question_type()
    {
        // Criar questões com diferentes tipos
        $multipleChoice = Question::factory()->create([
            'type' => 'multiple_choice'
        ]);
        
        $trueFalse = Question::factory()->create([
            'type' => 'true_false'
        ]);
        
        $essay = Question::factory()->create([
            'type' => 'essay'
        ]);

        $multipleChoiceQuestions = Question::ofType('multiple_choice')->get();
        $trueFalseQuestions = Question::ofType('true_false')->get();
        $essayQuestions = Question::ofType('essay')->get();
        
        $this->assertEquals(1, $multipleChoiceQuestions->count());
        $this->assertEquals(1, $trueFalseQuestions->count());
        $this->assertEquals(1, $essayQuestions->count());
    }
}
