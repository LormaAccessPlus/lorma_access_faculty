<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Activity;
use App\Models\Subject;
use App\Models\Faculty;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_belongs_to_subject(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        $activity = Activity::factory()->create(['subject_id' => $subject->id]);

        $this->assertInstanceOf(Subject::class, $activity->subject);
        $this->assertEquals($subject->id, $activity->subject->id);
    }

    public function test_activity_has_correct_fillable_attributes(): void
    {
        $activity = new Activity();
        
        $expectedFillable = [
            'subject_id',
            'name',
            'type',
            'term',
            'max_score',
            'weight',
            'gcr_assignment_id'
        ];

        $this->assertEquals($expectedFillable, $activity->getFillable());
    }

    public function test_activity_has_correct_casts(): void
    {
        $activity = new Activity();
        
        $expectedCasts = [
            'id' => 'int',
            'subject_id' => 'integer',
            'max_score' => 'decimal:2',
            'weight' => 'decimal:2',
        ];

        $this->assertEquals($expectedCasts, $activity->getCasts());
    }

    public function test_activity_can_be_created_with_all_attributes(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        
        $activityData = [
            'subject_id' => $subject->id,
            'name' => 'Test Quiz',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 50.00,
            'weight' => 15.50,
            'gcr_assignment_id' => 'gcr_123456'
        ];

        $activity = Activity::create($activityData);

        $this->assertDatabaseHas('activities', $activityData);
        $this->assertEquals('Test Quiz', $activity->name);
        $this->assertEquals('lecture', $activity->type);
        $this->assertEquals('prelim', $activity->term);
        $this->assertEquals(50.00, $activity->max_score);
        $this->assertEquals(15.50, $activity->weight);
        $this->assertEquals('gcr_123456', $activity->gcr_assignment_id);
    }

    public function test_activity_can_be_created_without_optional_attributes(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        
        $activityData = [
            'subject_id' => $subject->id,
            'name' => 'Test Quiz',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 50.00
        ];

        $activity = Activity::create($activityData);

        $this->assertDatabaseHas('activities', $activityData);
        $this->assertNull($activity->weight);
        $this->assertNull($activity->gcr_assignment_id);
    }

    public function test_activity_type_enum_values(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);

        // Test lecture type
        $lectureActivity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'type' => 'lecture'
        ]);
        $this->assertEquals('lecture', $lectureActivity->type);

        // Test lab type
        $labActivity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'type' => 'lab'
        ]);
        $this->assertEquals('lab', $labActivity->type);
    }

    public function test_activity_term_enum_values(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);

        // Test prelim term
        $prelimActivity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'prelim'
        ]);
        $this->assertEquals('prelim', $prelimActivity->term);

        // Test midterm term
        $midtermActivity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'midterm'
        ]);
        $this->assertEquals('midterm', $midtermActivity->term);

        // Test finals term
        $finalsActivity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'term' => 'finals'
        ]);
        $this->assertEquals('finals', $finalsActivity->term);
    }

    public function test_activity_max_score_is_cast_to_decimal(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        
        $activity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'max_score' => '75.50'
        ]);

        $this->assertEquals('75.50', $activity->max_score);
        $this->assertIsString($activity->max_score);
    }

    public function test_activity_weight_is_cast_to_decimal(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        
        $activity = Activity::factory()->create([
            'subject_id' => $subject->id,
            'weight' => '25.75'
        ]);

        $this->assertEquals('25.75', $activity->weight);
        $this->assertIsString($activity->weight);
    }

    public function test_activity_can_have_google_classroom_assignment_id(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);
        
        $activity = Activity::factory()->fromGoogleClassroom()->create([
            'subject_id' => $subject->id
        ]);

        $this->assertNotNull($activity->gcr_assignment_id);
        $this->assertIsString($activity->gcr_assignment_id);
    }

    public function test_activity_factory_states_work_correctly(): void
    {
        $faculty = Faculty::factory()->create();
        $subject = Subject::factory()->create(['faculty_id' => $faculty->id]);

        // Test lecture state
        $lectureActivity = Activity::factory()->lecture()->create(['subject_id' => $subject->id]);
        $this->assertEquals('lecture', $lectureActivity->type);

        // Test lab state
        $labActivity = Activity::factory()->lab()->create(['subject_id' => $subject->id]);
        $this->assertEquals('lab', $labActivity->type);

        // Test prelim state
        $prelimActivity = Activity::factory()->prelim()->create(['subject_id' => $subject->id]);
        $this->assertEquals('prelim', $prelimActivity->term);

        // Test midterm state
        $midtermActivity = Activity::factory()->midterm()->create(['subject_id' => $subject->id]);
        $this->assertEquals('midterm', $midtermActivity->term);

        // Test finals state
        $finalsActivity = Activity::factory()->finals()->create(['subject_id' => $subject->id]);
        $this->assertEquals('finals', $finalsActivity->term);
    }
}
