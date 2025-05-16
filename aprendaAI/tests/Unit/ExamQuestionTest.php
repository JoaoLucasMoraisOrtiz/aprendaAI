<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExamQuestionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_question_can_belong_to_multiple_exams()
    {
        // Create a topic for the question
        $topic = Topic::factory()->create();
        
        // Create a question
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
        ]);
        
        // Create institution for exams
        $institution = Institution::factory()->create();
        
        // Create multiple exams
        $exam1 = Exam::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Exam 1',
        ]);
        
        $exam2 = Exam::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Exam 2',
        ]);
        
        // Attach exams to the question
        $question->exams()->attach([$exam1->id, $exam2->id]);
        
        // Assert the relationships
        $this->assertCount(2, $question->exams);
        $this->assertTrue($question->exams->contains($exam1));
        $this->assertTrue($question->exams->contains($exam2));
    }
    
    #[Test]
    public function an_exam_can_have_multiple_questions()
    {
        // Create a topic for questions
        $topic = Topic::factory()->create();
        
        // Create multiple questions
        $question1 = Question::factory()->create([
            'topic_id' => $topic->id,
            'content' => 'Question 1',
        ]);
        
        $question2 = Question::factory()->create([
            'topic_id' => $topic->id,
            'content' => 'Question 2',
        ]);
        
        // Create an institution and exam
        $institution = Institution::factory()->create();
        $exam = Exam::factory()->create([
            'institution_id' => $institution->id,
        ]);
        
        // Attach questions to the exam
        $exam->questions()->attach([$question1->id, $question2->id]);
        
        // Assert the relationships
        $this->assertCount(2, $exam->questions);
        $this->assertTrue($exam->questions->contains($question1));
        $this->assertTrue($exam->questions->contains($question2));
    }
    
    #[Test]
    public function it_can_create_exam_question_relationship_directly()
    {
        // Create a topic for question
        $topic = Topic::factory()->create();
        
        // Create a question
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
        ]);
        
        // Create an institution and exam
        $institution = Institution::factory()->create();
        $exam = Exam::factory()->create([
            'institution_id' => $institution->id,
        ]);
        
        // Create the relationship directly
        $pivotData = [
            'exam_id' => $exam->id,
            'question_id' => $question->id,
        ];
        
        // Insert into pivot table
        \DB::table('exam_question')->insert($pivotData);
        
        // Assert the relationships
        $this->assertCount(1, $exam->fresh()->questions);
        $this->assertCount(1, $question->fresh()->exams);
    }
}
