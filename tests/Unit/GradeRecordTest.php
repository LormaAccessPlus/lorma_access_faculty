<?php

namespace Tests\Unit;

use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Activity;
use App\Models\StudentMapping;
use App\Models\GradeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_record_belongs_to_student_mapping()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $activity = Activity::factory()->create(['subject_id' => $subject->id]);
        
        $gradeRecord = GradeRecord::factory()->create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'created_by' => $faculty->id
        ]);

        $this->assertInstanceOf(StudentMapping::class, $gradeRecord->studentMapping);
        $this->assertEquals($studentMapping->id, $gradeRecord->studentMapping->id);
    }

    public function test_grade_record_belongs_to_activity()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $activity = Activity::factory()->create(['subject_id' => $subject->id]);
        
        $gradeRecord = GradeRecord::factory()->create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'created_by' => $faculty->id
        ]);

        $this->assertInstanceOf(Activity::class, $gradeRecord->activity);
        $this->assertEquals($activity->id, $gradeRecord->activity->id);
    }

    public function test_grade_record_belongs_to_faculty_creator()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $activity = Activity::factory()->create(['subject_id' => $subject->id]);
        
        $gradeRecord = GradeRecord::factory()->create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'created_by' => $faculty->id
        ]);

        $this->assertInstanceOf(Faculty::class, $gradeRecord->createdBy);
        $this->assertEquals($faculty->id, $gradeRecord->createdBy->id);
    }

    public function test_calculate_percentage_method()
    {
        $gradeRecord = new GradeRecord([
            'score' => 85,
            'max_score' => 100
        ]);

        $gradeRecord->calculatePercentage();

        $this->assertEquals(85.0, $gradeRecord->percentage);
    }

    public function test_calculate_percentage_with_different_max_score()
    {
        $gradeRecord = new GradeRecord([
            'score' => 42.5,
            'max_score' => 50
        ]);

        $gradeRecord->calculatePercentage();

        $this->assertEquals(85.0, $gradeRecord->percentage);
    }

    public function test_calculate_percentage_with_null_score()
    {
        $gradeRecord = new GradeRecord([
            'score' => null,
            'max_score' => 100
        ]);

        $gradeRecord->calculatePercentage();

        $this->assertNull($gradeRecord->percentage);
    }

    public function test_calculate_percentage_with_zero_max_score()
    {
        $gradeRecord = new GradeRecord([
            'score' => 50,
            'max_score' => 0
        ]);

        $gradeRecord->calculatePercentage();

        $this->assertNull($gradeRecord->percentage);
    }

    public function test_percentage_calculated_automatically_on_save()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $activity = Activity::factory()->create(['subject_id' => $subject->id, 'max_score' => 50]);
        
        $gradeRecord = GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 40,
            'max_score' => 50,
            'term' => 'prelim',
            'created_by' => $faculty->id
        ]);

        $this->assertEquals(80.0, $gradeRecord->percentage);
    }

    public function test_percentage_updated_when_score_changes()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $activity = Activity::factory()->create(['subject_id' => $subject->id, 'max_score' => 100]);
        
        $gradeRecord = GradeRecord::create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'score' => 75,
            'max_score' => 100,
            'term' => 'prelim',
            'created_by' => $faculty->id
        ]);

        $this->assertEquals(75.0, $gradeRecord->percentage);

        $gradeRecord->update(['score' => 90]);

        $this->assertEquals(90.0, $gradeRecord->percentage);
    }

    public function test_casts_are_applied_correctly()
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $studentMapping = StudentMapping::factory()->create(['subject_id' => $subject->id]);
        $activity = Activity::factory()->create(['subject_id' => $subject->id]);
        
        $gradeRecord = GradeRecord::factory()->create([
            'student_mapping_id' => $studentMapping->id,
            'activity_id' => $activity->id,
            'score' => '85.50',
            'max_score' => '100.00',
            'percentage' => '85.50',
            'created_by' => $faculty->id
        ]);

        $this->assertIsString($gradeRecord->score);
        $this->assertIsString($gradeRecord->max_score);
        $this->assertIsString($gradeRecord->percentage);
        $this->assertEquals('85.50', $gradeRecord->score);
        $this->assertEquals('100.00', $gradeRecord->max_score);
        $this->assertEquals('85.50', $gradeRecord->percentage);
        $this->assertIsInt($gradeRecord->student_mapping_id);
        $this->assertIsInt($gradeRecord->activity_id);
        $this->assertIsInt($gradeRecord->created_by);
    }
}
