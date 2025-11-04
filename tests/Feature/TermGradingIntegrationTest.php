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

class TermGradingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $faculty;
    private Subject $subject;
    private StudentMapping $student1;
    private StudentMapping $student2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faculty = Faculty::factory()->create();
        $this->subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'type' => 'lecture_lab'
        ]);
        
        $this->student1 = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'student_name' => 'John Doe',
            'student_email' => 'john.doe@example.com'
        ]);
        
        $this->student2 = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'student_name' => 'Jane Smith',
            'student_email' => 'jane.smith@example.com'
        ]);
        
        // Mock authentication
        $this->actingAs($this->faculty, 'faculty');
    }

    public function test_complete_term_grading_workflow()
    {
        // Step 1: Create activities for prelim term
        $lectureActivity1 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Quiz 1',
            'term' => 'prelim',
            'type' => 'lecture',
            'max_score' => 50
        ]);
        
        $lectureActivity2 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Assignment 1',
            'term' => 'prelim',
            'type' => 'lecture',
            'max_score' => 50
        ]);
        
        $labActivity1 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Lab Exercise 1',
            'term' => 'prelim',
            'type' => 'lab',
            'max_score' => 100
        ]);

        // Step 2: Access the term grading interface
        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
        $response->assertSee('Quiz 1');
        $response->assertSee('Assignment 1');
        $response->assertSee('Lab Exercise 1');

        // Step 3: Input activity grades for student 1
        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student1->id,
            'activity_id' => $lectureActivity1->id,
            'score' => 45
        ])->assertStatus(200);

        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student1->id,
            'activity_id' => $lectureActivity2->id,
            'score' => 40
        ])->assertStatus(200);

        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student1->id,
            'activity_id' => $labActivity1->id,
            'score' => 85
        ])->assertStatus(200);

        // Step 4: Verify class standing is calculated automatically
        $termGrade = TermGrade::where('student_mapping_id', $this->student1->id)
            ->where('term', 'prelim')
            ->first();
        
        $this->assertNotNull($termGrade);
        // Total: (45+40+85) = 170, Max: (50+50+100) = 200, Percentage: 85%
        $this->assertEquals(85.0, $termGrade->class_standing);

        // Step 5: Input exam score
        $this->postJson(route('grades.update-exam-score'), [
            'student_mapping_id' => $this->student1->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'exam_score' => 90
        ])->assertStatus(200);

        // Step 6: Verify term grade is calculated
        $termGrade->refresh();
        $this->assertEquals(90.0, $termGrade->exam_score);
        $this->assertEquals(90.0, $termGrade->exam_grade); // For prelim, exam_grade = exam_score
        
        // Term grade: (85 * 0.4) + (90 * 0.6) = 34 + 54 = 88
        $this->assertEquals(88.0, $termGrade->term_grade);

        // Step 7: Input grades for student 2 (partial)
        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student2->id,
            'activity_id' => $lectureActivity1->id,
            'score' => 48
        ])->assertStatus(200);

        // Step 8: Check progress calculation
        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $termProgress = $response->viewData('termProgress');
        
        // Only 1 out of 2 students has complete term grade
        $this->assertEquals(2, $termProgress['prelim']['total_students']);
        $this->assertEquals(1, $termProgress['prelim']['students_with_grades']);
        $this->assertEquals(50.0, $termProgress['prelim']['percentage']);

        // Step 9: Navigate to different term and back
        $midtermResponse = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'midterm']));
        $midtermResponse->assertStatus(200);
        
        $prelimResponse = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $prelimResponse->assertStatus(200);
        
        // Verify data persistence
        $termGrades = $prelimResponse->viewData('termGrades');
        $this->assertNotEmpty($termGrades);
        $this->assertEquals(88.0, $termGrades[$this->student1->id]->term_grade);

        // Step 10: Complete student 2's grades
        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student2->id,
            'activity_id' => $lectureActivity2->id,
            'score' => 47
        ])->assertStatus(200);

        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student2->id,
            'activity_id' => $labActivity1->id,
            'score' => 95
        ])->assertStatus(200);

        $this->postJson(route('grades.update-exam-score'), [
            'student_mapping_id' => $this->student2->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'exam_score' => 88
        ])->assertStatus(200);

        // Step 11: Verify final progress
        $response = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $termProgress = $response->viewData('termProgress');
        
        // Now both students should have complete grades
        $this->assertEquals(2, $termProgress['prelim']['total_students']);
        $this->assertEquals(2, $termProgress['prelim']['students_with_grades']);
        $this->assertEquals(100.0, $termProgress['prelim']['percentage']);
    }

    public function test_midterm_term_grade_calculation_workflow()
    {
        // Create midterm activities
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Midterm Project',
            'term' => 'midterm',
            'type' => 'lecture',
            'max_score' => 100
        ]);

        // Input activity grade: 80/100 = 80%
        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student1->id,
            'activity_id' => $activity->id,
            'score' => 80
        ])->assertStatus(200);

        // Verify class standing calculation for midterm
        $termGrade = TermGrade::where('student_mapping_id', $this->student1->id)
            ->where('term', 'midterm')
            ->first();
        
        $this->assertNotNull($termGrade);
        // For midterm: (80/100) * 50 + 50 = 40 + 50 = 90
        $this->assertEquals(90.0, $termGrade->class_standing);

        // Input exam score
        $this->postJson(route('grades.update-exam-score'), [
            'student_mapping_id' => $this->student1->id,
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'exam_score' => 85
        ])->assertStatus(200);

        $termGrade->refresh();
        
        // Exam grade: (85/100) * 50 + 50 = 42.5 + 50 = 92.5
        $this->assertEquals(92.5, $termGrade->exam_grade);
        
        // Term grade: (90 * 0.4) + (92.5 * 0.6) = 36 + 55.5 = 91.5
        $this->assertEquals(91.5, $termGrade->term_grade);
    }

    public function test_term_navigation_and_data_isolation()
    {
        // Create activities for different terms
        $prelimActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'type' => 'lecture',
            'max_score' => 100
        ]);
        
        $midtermActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'type' => 'lecture',
            'max_score' => 100
        ]);
        
        $finalsActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'finals',
            'type' => 'lecture',
            'max_score' => 100
        ]);

        // Add grades for each term
        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student1->id,
            'activity_id' => $prelimActivity->id,
            'score' => 85
        ])->assertStatus(200);

        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student1->id,
            'activity_id' => $midtermActivity->id,
            'score' => 90
        ])->assertStatus(200);

        $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->student1->id,
            'activity_id' => $finalsActivity->id,
            'score' => 95
        ])->assertStatus(200);

        // Test that each term view shows only relevant data
        $prelimResponse = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'prelim']));
        $prelimResponse->assertStatus(200);
        $prelimActivities = $prelimResponse->viewData('lectureActivities');
        $this->assertEquals(1, $prelimActivities->count());
        $this->assertEquals('prelim', $prelimActivities->first()->term);

        $midtermResponse = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'midterm']));
        $midtermResponse->assertStatus(200);
        $midtermActivities = $midtermResponse->viewData('lectureActivities');
        $this->assertEquals(1, $midtermActivities->count());
        $this->assertEquals('midterm', $midtermActivities->first()->term);

        $finalsResponse = $this->get(route('grades.term', ['subject' => $this->subject, 'term' => 'finals']));
        $finalsResponse->assertStatus(200);
        $finalsActivities = $finalsResponse->viewData('lectureActivities');
        $this->assertEquals(1, $finalsActivities->count());
        $this->assertEquals('finals', $finalsActivities->first()->term);
    }
}