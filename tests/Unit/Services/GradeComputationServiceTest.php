<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\FinalRating;
use App\Models\GradeRecord;
use App\Models\StudentMapping;
use App\Models\Subject;
use App\Models\TermGrade;
use App\Services\GradeComputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeComputationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradeComputationService $service;
    private Subject $subject;
    private StudentMapping $studentMapping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GradeComputationService();
        
        // Create test data
        $this->subject = Subject::factory()->create();
        $this->studentMapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);
    }

    public function test_compute_class_standing_prelim_percentage_formula()
    {
        // Create activities for prelim term
        $activity1 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 50
        ]);
        
        $activity2 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 30
        ]);

        // Create grade records
        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity1->id,
            'score' => 40,
            'max_score' => 50
        ]);

        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity2->id,
            'score' => 24,
            'max_score' => 30
        ]);

        $classStanding = $this->service->computeClassStanding($this->studentMapping, $this->subject, 'prelim');

        // Expected: (40 + 24) / (50 + 30) * 100 = 64/80 * 100 = 80%
        $this->assertEquals(80.0, $classStanding);
    }

    public function test_compute_class_standing_midterm_transmuted_formula()
    {
        // Create activities for midterm term
        $activity1 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'max_score' => 100
        ]);

        // Create grade record
        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity1->id,
            'score' => 80,
            'max_score' => 100
        ]);

        $classStanding = $this->service->computeClassStanding($this->studentMapping, $this->subject, 'midterm');

        // Expected: (80/100 * 50) + 50 = 40 + 50 = 90
        $this->assertEquals(90.0, $classStanding);
    }

    public function test_compute_class_standing_with_missing_grades()
    {
        // Create activities but no grade records
        Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 50
        ]);

        $classStanding = $this->service->computeClassStanding($this->studentMapping, $this->subject, 'prelim');

        // Expected: 0/50 * 100 = 0%
        $this->assertEquals(0.0, $classStanding);
    }

    public function test_compute_class_standing_no_activities()
    {
        $classStanding = $this->service->computeClassStanding($this->studentMapping, $this->subject, 'prelim');

        $this->assertEquals(0.0, $classStanding);
    }

    public function test_compute_exam_grade_prelim_percentage()
    {
        $examGrade = $this->service->computeExamGrade(80, 100, 'prelim', $this->subject);

        // Expected: 80/100 * 100 = 80%
        $this->assertEquals(80.0, $examGrade);
    }

    public function test_compute_exam_grade_midterm_transmuted()
    {
        $examGrade = $this->service->computeExamGrade(80, 100, 'midterm', $this->subject);

        // Expected: (80/100 * 50) + 50 = 90
        $this->assertEquals(90.0, $examGrade);
    }

    public function test_compute_exam_grade_finals_transmuted()
    {
        $examGrade = $this->service->computeExamGrade(75, 100, 'finals', $this->subject);

        // Expected: (75/100 * 50) + 50 = 87.5
        $this->assertEquals(87.5, $examGrade);
    }

    public function test_compute_exam_grade_zero_max_score()
    {
        $examGrade = $this->service->computeExamGrade(80, 0, 'prelim', $this->subject);

        $this->assertEquals(0.0, $examGrade);
    }

    public function test_compute_term_grade_prelim()
    {
        // Create activity and grade record for class standing calculation
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 100
        ]);

        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 80,
            'max_score' => 100
        ]);

        $termGrade = $this->service->computeTermGrade($this->studentMapping, $this->subject, 'prelim', 85, 100);

        // Class standing: 80/100 * 100 = 80%
        // Exam grade: 85/100 * 100 = 85%
        // Term grade: (80 * 0.4) + (85 * 0.6) = 32 + 51 = 83%
        $this->assertEquals(83.0, $termGrade->term_grade);
        $this->assertEquals(80.0, $termGrade->class_standing);
        $this->assertEquals(85.0, $termGrade->exam_grade);
        $this->assertEquals(85.0, $termGrade->exam_score);
    }

    public function test_compute_term_grade_midterm()
    {
        // Create activity and grade record for class standing calculation
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'max_score' => 100
        ]);

        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 80,
            'max_score' => 100
        ]);

        $termGrade = $this->service->computeTermGrade($this->studentMapping, $this->subject, 'midterm', 75, 100);

        // Class standing: (80/100 * 50) + 50 = 90%
        // Exam grade: (75/100 * 50) + 50 = 87.5%
        // Term grade: (90 * 0.4) + (87.5 * 0.6) = 36 + 52.5 = 88.5%
        $this->assertEquals(88.5, $termGrade->term_grade);
        $this->assertEquals(90.0, $termGrade->class_standing);
        $this->assertEquals(87.5, $termGrade->exam_grade);
    }

    public function test_compute_term_grade_without_exam()
    {
        // Create activity and grade record for class standing calculation
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 100
        ]);

        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 80,
            'max_score' => 100
        ]);

        $termGrade = $this->service->computeTermGrade($this->studentMapping, $this->subject, 'prelim');

        // Class standing: 80%
        // Exam grade: 0% (no exam)
        // Term grade: (80 * 0.4) + (0 * 0.6) = 32%
        $this->assertEquals(32.0, $termGrade->term_grade);
        $this->assertEquals(80.0, $termGrade->class_standing);
        $this->assertEquals(0.0, $termGrade->exam_grade);
        $this->assertNull($termGrade->exam_score);
    }

    public function test_compute_final_rating()
    {
        // Create term grades
        TermGrade::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 85.0
        ]);

        TermGrade::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'midterm',
            'term_grade' => 88.0
        ]);

        TermGrade::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'finals',
            'term_grade' => 90.0
        ]);

        $finalRating = $this->service->computeFinalRating($this->studentMapping, $this->subject, '2024-2025', '1st');

        // Final rating: (85 * 0.3) + (88 * 0.3) + (90 * 0.4) = 25.5 + 26.4 + 36 = 87.9
        $this->assertEquals(87.9, $finalRating->final_rating);
        $this->assertEquals(85.0, $finalRating->prelim_grade);
        $this->assertEquals(88.0, $finalRating->midterm_grade);
        $this->assertEquals(90.0, $finalRating->finals_grade);
        $this->assertEquals('2024-2025', $finalRating->academic_year);
        $this->assertEquals('1st', $finalRating->semester);
    }

    public function test_compute_final_rating_with_missing_term_grades()
    {
        // Create only prelim grade
        TermGrade::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 85.0
        ]);

        $finalRating = $this->service->computeFinalRating($this->studentMapping, $this->subject, '2024-2025', '1st');

        // Final rating: (85 * 0.3) + (0 * 0.3) + (0 * 0.4) = 25.5
        $this->assertEquals(25.5, $finalRating->final_rating);
        $this->assertEquals(85.0, $finalRating->prelim_grade);
        $this->assertEquals(0.0, $finalRating->midterm_grade);
        $this->assertEquals(0.0, $finalRating->finals_grade);
    }

    public function test_compute_all_term_grades()
    {
        // Create another student mapping
        $studentMapping2 = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        // Create activity
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 100
        ]);

        // Create grade records
        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 80,
            'max_score' => 100
        ]);

        GradeRecord::factory()->create([
            'student_mapping_id' => $studentMapping2->id,
            'activity_id' => $activity->id,
            'score' => 90,
            'max_score' => 100
        ]);

        $examScores = [
            $this->studentMapping->id => ['score' => 85, 'max_score' => 100],
            $studentMapping2->id => ['score' => 95, 'max_score' => 100]
        ];

        $termGrades = $this->service->computeAllTermGrades($this->subject, 'prelim', $examScores);

        $this->assertCount(2, $termGrades);
        
        $grade1 = $termGrades->where('student_mapping_id', $this->studentMapping->id)->first();
        $grade2 = $termGrades->where('student_mapping_id', $studentMapping2->id)->first();

        // Student 1: (80 * 0.4) + (85 * 0.6) = 83%
        $this->assertEquals(83.0, $grade1->term_grade);
        
        // Student 2: (90 * 0.4) + (95 * 0.6) = 93%
        $this->assertEquals(93.0, $grade2->term_grade);
    }

    public function test_validate_computation_inputs_valid()
    {
        $inputs = [
            'exam_score' => 85,
            'exam_max_score' => 100,
            'term' => 'prelim'
        ];

        $errors = $this->service->validateComputationInputs($inputs);

        $this->assertEmpty($errors);
    }

    public function test_validate_computation_inputs_negative_score()
    {
        $inputs = [
            'exam_score' => -10,
            'exam_max_score' => 100
        ];

        $errors = $this->service->validateComputationInputs($inputs);

        $this->assertContains('Exam score cannot be negative', $errors);
    }

    public function test_validate_computation_inputs_zero_max_score()
    {
        $inputs = [
            'exam_score' => 85,
            'exam_max_score' => 0
        ];

        $errors = $this->service->validateComputationInputs($inputs);

        $this->assertContains('Exam max score must be greater than zero', $errors);
    }

    public function test_validate_computation_inputs_score_exceeds_max()
    {
        $inputs = [
            'exam_score' => 110,
            'exam_max_score' => 100
        ];

        $errors = $this->service->validateComputationInputs($inputs);

        $this->assertContains('Exam score cannot exceed max score', $errors);
    }

    public function test_validate_computation_inputs_invalid_term()
    {
        $inputs = [
            'term' => 'invalid_term'
        ];

        $errors = $this->service->validateComputationInputs($inputs);

        $this->assertContains('Invalid term specified', $errors);
    }

    public function test_get_grade_statistics()
    {
        // Create multiple term grades
        TermGrade::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 85.0
        ]);

        TermGrade::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 90.0
        ]);

        TermGrade::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 70.0
        ]);

        TermGrade::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 95.0
        ]);

        $stats = $this->service->getGradeStatistics($this->subject, 'prelim');

        $this->assertEquals(4, $stats['count']);
        $this->assertEquals(85.0, $stats['average']); // (85+90+70+95)/4 = 85
        $this->assertEquals(95.0, $stats['highest']);
        $this->assertEquals(70.0, $stats['lowest']);
        $this->assertEquals(3, $stats['passing_count']); // 85, 90, 95 are >= 75
        $this->assertEquals(75.0, $stats['passing_rate']); // 3/4 * 100 = 75%
    }

    public function test_get_grade_statistics_no_grades()
    {
        $stats = $this->service->getGradeStatistics($this->subject, 'prelim');

        $this->assertEquals(0, $stats['count']);
        $this->assertEquals(0, $stats['average']);
        $this->assertEquals(0, $stats['highest']);
        $this->assertEquals(0, $stats['lowest']);
        $this->assertEquals(0, $stats['passing_count']);
        $this->assertEquals(0, $stats['passing_rate']);
    }

    public function test_term_grade_updates_existing_record()
    {
        // Create initial term grade
        $initialTermGrade = TermGrade::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'term_grade' => 80.0
        ]);

        // Create activity for new computation
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'term' => 'prelim',
            'max_score' => 100
        ]);

        GradeRecord::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 90,
            'max_score' => 100
        ]);

        // Compute new term grade
        $updatedTermGrade = $this->service->computeTermGrade($this->studentMapping, $this->subject, 'prelim', 85, 100);

        // Should update the existing record, not create a new one
        $this->assertEquals($initialTermGrade->id, $updatedTermGrade->id);
        $this->assertEquals(83.0, $updatedTermGrade->term_grade); // (90 * 0.4) + (85 * 0.6)
        
        // Verify only one record exists
        $this->assertEquals(1, TermGrade::where('student_mapping_id', $this->studentMapping->id)
            ->where('subject_id', $this->subject->id)
            ->where('term', 'prelim')
            ->count());
    }
}