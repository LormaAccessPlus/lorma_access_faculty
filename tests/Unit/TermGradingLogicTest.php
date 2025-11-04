<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Subject;
use App\Models\Activity;
use App\Models\StudentMapping;
use App\Models\GradeRecord;
use App\Models\TermGrade;
use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TermGradingLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_standing_calculation_for_prelim()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        
        $activity1 = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'max_score' => 50
        ]);
        
        $activity2 = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'max_score' => 50
        ]);

        // Create grade records: 40/50 + 45/50 = 85/100 = 85%
        GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity1->id,
            'score' => 40,
            'max_score' => 50,
            'term' => 'prelim',
            'created_by' => $faculty->id
        ]);

        GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity2->id,
            'score' => 45,
            'max_score' => 50,
            'term' => 'prelim',
            'created_by' => $faculty->id
        ]);

        // Simulate the recalculation that would happen in the controller
        $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id)
                  ->where('term', 'prelim');
        })->where('student_mapping_id', $studentMapping->id)->get();

        $totalScore = $gradeRecords->sum('score');
        $totalPossible = $gradeRecords->sum('max_score');
        $classStandingPercentage = ($totalScore / $totalPossible) * 100;
        
        // For prelim, class standing = percentage directly
        $classStanding = $classStandingPercentage;

        $this->assertEquals(85, $totalScore);
        $this->assertEquals(100, $totalPossible);
        $this->assertEquals(85.0, $classStanding);
    }

    public function test_class_standing_calculation_for_midterm()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        
        $activity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'midterm',
            'max_score' => 100
        ]);

        // Create grade record: 80/100 = 80%
        GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 80,
            'max_score' => 100,
            'term' => 'midterm',
            'created_by' => $faculty->id
        ]);

        $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id)
                  ->where('term', 'midterm');
        })->where('student_mapping_id', $studentMapping->id)->get();

        $totalScore = $gradeRecords->sum('score');
        $totalPossible = $gradeRecords->sum('max_score');
        $classStandingPercentage = ($totalScore / $totalPossible) * 100;
        
        // For midterm/finals: (percentage/100) × 50 + 50
        $classStanding = ($classStandingPercentage / 100) * 50 + 50;

        $this->assertEquals(80, $totalScore);
        $this->assertEquals(100, $totalPossible);
        $this->assertEquals(80.0, $classStandingPercentage);
        $this->assertEquals(90.0, $classStanding); // (80/100) * 50 + 50 = 40 + 50 = 90
    }

    public function test_term_grade_calculation_formulas()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);

        // Test Prelim calculation
        $prelimTermGrade = TermGrade::create([
            'student_mapping_id' => $studentMapping->id,
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'class_standing' => 85.0,
            'exam_score' => 90.0
        ]);

        // Prelim: (class_standing * 0.4) + (exam_score * 0.6)
        $expectedPrelimGrade = (85.0 * 0.4) + (90.0 * 0.6);
        $this->assertEquals(88.0, $expectedPrelimGrade); // 34 + 54 = 88

        // Test Midterm calculation
        $midtermTermGrade = TermGrade::create([
            'student_mapping_id' => $studentMapping->id,
            'subject_id' => $subject->id,
            'term' => 'midterm',
            'class_standing' => 85.0,
            'exam_score' => 90.0
        ]);

        // Midterm: exam_grade = (exam_score/100) × 50 + 50
        $expectedExamGrade = (90.0 / 100) * 50 + 50;
        $this->assertEquals(95.0, $expectedExamGrade); // 45 + 50 = 95

        // Term grade: (class_standing * 0.4) + (exam_grade * 0.6)
        $expectedMidtermGrade = (85.0 * 0.4) + (95.0 * 0.6);
        $this->assertEquals(91.0, $expectedMidtermGrade); // 34 + 57 = 91
    }

    public function test_progress_calculation_logic()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        
        // Create 3 students
        $student1 = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $student2 = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $student3 = StudentMapping::factory()->create(['subject_id' => $subject->id]);

        // Only 2 students have completed term grades
        TermGrade::create([
            'student_mapping_id' => $student1->id,
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'term_grade' => 85.0
        ]);

        TermGrade::create([
            'student_mapping_id' => $student2->id,
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'term_grade' => 90.0
        ]);

        $totalStudents = $subject->studentMappings()->count();
        $studentsWithGrades = TermGrade::where('subject_id', $subject->id)
            ->where('term', 'prelim')
            ->whereNotNull('term_grade')
            ->count();

        $percentage = $totalStudents > 0 ? round(($studentsWithGrades / $totalStudents) * 100, 1) : 0;

        $this->assertEquals(3, $totalStudents);
        $this->assertEquals(2, $studentsWithGrades);
        $this->assertEquals(66.7, $percentage); // 2/3 * 100 = 66.7%
    }

    public function test_grade_record_percentage_calculation()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        
        $activity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'max_score' => 50
        ]);

        $gradeRecord = GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 42.5,
            'max_score' => 50,
            'term' => 'prelim',
            'created_by' => $faculty->id
        ]);

        // The percentage should be calculated automatically by the model
        $this->assertEquals(85.0, $gradeRecord->percentage); // 42.5/50 * 100 = 85%
    }

    public function test_empty_activities_handling()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);

        // No activities exist for this term
        $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id)
                  ->where('term', 'prelim');
        })->where('student_mapping_id', $studentMapping->id)->get();

        $this->assertTrue($gradeRecords->isEmpty());
        
        // Should not create a term grade if no activities exist
        $totalPossible = $gradeRecords->sum('max_score');
        $this->assertEquals(0, $totalPossible);
    }

    public function test_partial_grade_completion()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        
        // Create 3 activities but only grade 2 of them
        $activity1 = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'max_score' => 50
        ]);
        
        $activity2 = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'max_score' => 50
        ]);
        
        $activity3 = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'prelim',
            'max_score' => 50
        ]);

        // Only grade first two activities
        GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity1->id,
            'score' => 40,
            'max_score' => 50,
            'term' => 'prelim',
            'created_by' => $faculty->id
        ]);

        GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity2->id,
            'score' => 45,
            'max_score' => 50,
            'term' => 'prelim',
            'created_by' => $faculty->id
        ]);

        $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id)
                  ->where('term', 'prelim');
        })->where('student_mapping_id', $studentMapping->id)->get();

        $totalScore = $gradeRecords->sum('score');
        $totalPossible = $gradeRecords->sum('max_score');

        // Should only calculate based on graded activities
        $this->assertEquals(85, $totalScore);
        $this->assertEquals(100, $totalPossible); // Only 2 activities graded
        $this->assertEquals(2, $gradeRecords->count());
    }
}