<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Subject;
use App\Models\Activity;
use App\Models\StudentMapping;
use App\Models\GradeRecord;
use App\Models\TermGrade;
use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TermGradingTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $faculty;
    private Subject $subject;
    private StudentMapping $studentMapping;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faculty = Faculty::factory()->create();
        $this->subject = Subject::factory()->create(['faculty_id' => $this->faculty->id]);
        $this->studentMapping = StudentMapping::factory()->create(['subject_id' => $this->subject->id]);
        
        // Mock authentication
        $this->actingAs($this->faculty, 'faculty');
    }

    public function test_can_view_term_grades_page()
    {
        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        
        $response->assertStatus(200);
        $response->assertViewIs('grades.term-grades');
        $response->assertViewHas(['subject', 'term', 'termProgress']);
    }

    public function test_invalid_term_returns_404()
    {
        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'invalid']));
        
        $response->assertStatus(404);
    }

    public function test_term_navigation_shows_correct_activities()
    {
        // Create activities for different terms
        $prelimActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'type' => 'lecture'
        ]);
        
        $midtermActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'type' => 'lecture'
        ]);

        // Test prelim term view
        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $response->assertStatus(200);
        $response->assertSee($prelimActivity->name);
        $response->assertDontSee($midtermActivity->name);

        // Test midterm term view
        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'midterm']));
        $response->assertStatus(200);
        $response->assertSee($midtermActivity->name);
        $response->assertDontSee($prelimActivity->name);
    }

    public function test_can_update_exam_score()
    {
        $data = [
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'exam_score' => 85.5
        ];

        $response = $this->postJson(route('grades.update-exam-score'), $data);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('term_grades', [
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'exam_score' => 85.5
        ]);
    }

    public function test_exam_score_validation()
    {
        // Test negative score
        $data = [
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'exam_score' => -10
        ];

        $response = $this->postJson(route('grades.update-exam-score'), $data);
        $response->assertStatus(422);

        // Test score over 100
        $data['exam_score'] = 150;
        $response = $this->postJson(route('grades.update-exam-score'), $data);
        $response->assertStatus(422);
    }

    public function test_term_grade_calculation_prelim()
    {
        // Create term grade with class standing and exam score
        $termGrade = TermGrade::create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'class_standing' => 80.0,
            'exam_score' => 90.0
        ]);

        $data = [
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'exam_score' => 90.0
        ];

        $response = $this->postJson(route('grades.update-exam-score'), $data);
        
        $response->assertStatus(200);
        
        $termGrade->refresh();
        
        // For prelim: (class_standing * 0.4) + (exam_score * 0.6)
        $expectedTermGrade = (80.0 * 0.4) + (90.0 * 0.6);
        $this->assertEquals(round($expectedTermGrade, 2), $termGrade->term_grade);
        $this->assertEquals(90.0, $termGrade->exam_grade); // For prelim, exam_grade = exam_score
    }

    public function test_term_grade_calculation_midterm()
    {
        $termGrade = TermGrade::create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'class_standing' => 80.0,
            'exam_score' => 90.0
        ]);

        $data = [
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'exam_score' => 90.0
        ];

        $response = $this->postJson(route('grades.update-exam-score'), $data);
        
        $response->assertStatus(200);
        
        $termGrade->refresh();
        
        // For midterm: exam_grade = (exam_score/100) × 50 + 50
        $expectedExamGrade = (90.0 / 100) * 50 + 50;
        $expectedTermGrade = (80.0 * 0.4) + ($expectedExamGrade * 0.6);
        
        $this->assertEquals(round($expectedExamGrade, 2), $termGrade->exam_grade);
        $this->assertEquals(round($expectedTermGrade, 2), $termGrade->term_grade);
    }

    public function test_class_standing_recalculation()
    {
        // Create activities for prelim term
        $activity1 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'type' => 'lecture',
            'max_score' => 50
        ]);
        
        $activity2 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'type' => 'lecture',
            'max_score' => 50
        ]);

        // Add grade records
        GradeRecord::create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity1->id,
            'score' => 40,
            'max_score' => 50,
            'term' => 'prelim',
            'created_by' => $this->faculty->id
        ]);

        $data = [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity2->id,
            'score' => 45
        ];

        $response = $this->postJson(route('grades.update'), $data);
        
        $response->assertStatus(200);
        
        // Check if class standing was calculated correctly
        $termGrade = TermGrade::where('student_mapping_id', $this->studentMapping->id)
            ->where('term', 'prelim')
            ->first();
        
        $this->assertNotNull($termGrade);
        
        // Total score: 40 + 45 = 85, Total possible: 50 + 50 = 100
        // Class standing for prelim: (85/100) * 100 = 85%
        $this->assertEquals(85.0, $termGrade->class_standing);
    }

    public function test_term_progress_calculation()
    {
        // Create term grades for different students
        $student2 = StudentMapping::factory()->create(['subject_id' => $this->subject->id]);
        
        TermGrade::create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 85.0
        ]);

        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        
        $response->assertStatus(200);
        
        $termProgress = $response->viewData('termProgress');
        
        // Should show 1 out of 2 students completed (50%)
        $this->assertEquals(2, $termProgress['prelim']['total_students']);
        $this->assertEquals(1, $termProgress['prelim']['students_with_grades']);
        $this->assertEquals(50.0, $termProgress['prelim']['percentage']);
    }

    public function test_can_get_term_grade_data()
    {
        $termGrade = TermGrade::create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'class_standing' => 80.0,
            'exam_score' => 90.0,
            'exam_grade' => 90.0,
            'term_grade' => 86.0
        ]);

        $data = [
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim'
        ];

        $response = $this->getJson(route('grades.get-term-grade', $data));
        
        $response->assertStatus(200);
        $response->assertJson([
            'class_standing' => 80.0,
            'exam_score' => 90.0,
            'exam_grade' => 90.0,
            'term_grade' => 86.0
        ]);
    }

    public function test_term_grade_persistence_across_navigation()
    {
        // Create activities and grades for multiple terms
        $prelimActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 100
        ]);
        
        $midtermActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'max_score' => 100
        ]);

        // Add grades for both terms
        GradeRecord::create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $prelimActivity->id,
            'score' => 85,
            'max_score' => 100,
            'term' => 'prelim',
            'created_by' => $this->faculty->id
        ]);

        GradeRecord::create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $midtermActivity->id,
            'score' => 90,
            'max_score' => 100,
            'term' => 'midterm',
            'created_by' => $this->faculty->id
        ]);

        // Create term grades
        TermGrade::create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'exam_score' => 88.0,
            'term_grade' => 86.2
        ]);

        TermGrade::create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'exam_score' => 92.0,
            'term_grade' => 90.8
        ]);

        // Test that data persists when navigating between terms
        $prelimResponse = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $prelimResponse->assertStatus(200);
        
        $midtermResponse = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'midterm']));
        $midtermResponse->assertStatus(200);
        
        // Navigate back to prelim and verify data is still there
        $prelimResponse2 = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $prelimResponse2->assertStatus(200);
        
        $termGrades = $prelimResponse2->viewData('termGrades');
        $this->assertNotEmpty($termGrades);
        $this->assertEquals(88.0, $termGrades[$this->studentMapping->id]->exam_score);
    }
}