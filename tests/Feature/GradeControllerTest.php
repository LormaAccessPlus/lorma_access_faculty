<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Activity;
use App\Models\StudentMapping;
use App\Models\GradeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $faculty;
    protected $subject;
    protected $activity;
    protected $studentMapping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Faculty::factory()->create();
        $this->subject = Subject::factory()->create(['faculty_id' => $this->faculty->id]);
        $this->activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Test Activity',
            'max_score' => 100,
            'term' => 'prelim'
        ]);
        $this->studentMapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'student_name' => 'Test Student'
        ]);

        $this->actingAs($this->faculty, 'faculty');
    }

    public function test_can_view_grade_matrix()
    {
        $response = $this->get(route('grades.matrix', $this->subject));

        $response->assertStatus(200);
        $response->assertViewIs('grades.matrix');
        $response->assertViewHas('subject');
        $response->assertViewHas('activitiesByTerm');
        $response->assertViewHas('gradeMatrix');
    }

    public function test_grade_matrix_displays_students_and_activities()
    {
        $response = $this->get(route('grades.matrix', $this->subject));

        $response->assertSee($this->studentMapping->student_name);
        $response->assertSee($this->activity->name);
        $response->assertSee($this->activity->max_score);
    }

    public function test_can_update_grade_record()
    {
        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 85.5
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'grade_record' => [
                'score' => 85.5,
                'percentage' => 85.5,
                'max_score' => 100
            ]
        ]);

        $this->assertDatabaseHas('grade_records', [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 85.5,
            'percentage' => 85.5,
            'created_by' => $this->faculty->id
        ]);
    }

    public function test_can_update_existing_grade_record()
    {
        // Create initial grade record
        $gradeRecord = GradeRecord::create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 75,
            'max_score' => 100,
            'term' => 'prelim',
            'created_by' => $this->faculty->id
        ]);

        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 90
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'grade_record' => [
                'score' => 90,
                'percentage' => 90,
                'max_score' => 100
            ]
        ]);

        // Should update existing record, not create new one
        $this->assertEquals(1, GradeRecord::count());
        $gradeRecord->refresh();
        $this->assertEquals(90, $gradeRecord->score);
        $this->assertEquals(90, $gradeRecord->percentage);
    }

    public function test_can_clear_grade_by_setting_null_score()
    {
        // Create initial grade record
        GradeRecord::create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 75,
            'max_score' => 100,
            'term' => 'prelim',
            'created_by' => $this->faculty->id
        ]);

        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => null
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'grade_record' => [
                'score' => null,
                'percentage' => null,
                'max_score' => 100
            ]
        ]);
    }

    public function test_validates_score_does_not_exceed_max_score()
    {
        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 150 // Exceeds max_score of 100
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Score cannot exceed maximum score'
        ]);
    }

    public function test_validates_activity_belongs_to_same_subject()
    {
        $otherSubject = Subject::factory()->create(['faculty_id' => $this->faculty->id]);
        $otherActivity = Activity::factory()->create(['subject_id' => $otherSubject->id]);

        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $otherActivity->id,
            'score' => 85
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Invalid activity for this subject'
        ]);
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson(route('grades.update'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_mapping_id', 'activity_id']);
    }

    public function test_validates_student_mapping_exists()
    {
        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => 999,
            'activity_id' => $this->activity->id,
            'score' => 85
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_mapping_id']);
    }

    public function test_validates_activity_exists()
    {
        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => 999,
            'score' => 85
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['activity_id']);
    }

    public function test_validates_score_is_numeric()
    {
        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 'invalid'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['score']);
    }

    public function test_validates_score_is_not_negative()
    {
        $response = $this->postJson(route('grades.update'), [
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => -10
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['score']);
    }

    public function test_can_get_existing_grade()
    {
        $gradeRecord = GradeRecord::create([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id,
            'score' => 85,
            'max_score' => 100,
            'term' => 'prelim',
            'created_by' => $this->faculty->id
        ]);

        $response = $this->getJson(route('grades.get') . '?' . http_build_query([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'score' => 85,
            'percentage' => 85,
            'max_score' => 100
        ]);
    }

    public function test_returns_null_for_non_existent_grade()
    {
        $response = $this->getJson(route('grades.get') . '?' . http_build_query([
            'student_mapping_id' => $this->studentMapping->id,
            'activity_id' => $this->activity->id
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'score' => null,
            'percentage' => null
        ]);
    }

    public function test_grade_matrix_shows_empty_state_when_no_students()
    {
        $subjectWithoutStudents = Subject::factory()->create(['faculty_id' => $this->faculty->id]);

        $response = $this->get(route('grades.matrix', $subjectWithoutStudents));

        $response->assertStatus(200);
        $response->assertSee('No students mapped for this subject');
    }

    public function test_grade_matrix_shows_empty_state_when_no_activities()
    {
        $subjectWithoutActivities = Subject::factory()->create(['faculty_id' => $this->faculty->id]);
        StudentMapping::factory()->create(['subject_id' => $subjectWithoutActivities->id]);

        $response = $this->get(route('grades.matrix', $subjectWithoutActivities));

        $response->assertStatus(200);
        $response->assertSee('No activities created for this subject');
    }

    public function test_grade_matrix_separates_lecture_and_lab_activities()
    {
        // Update subject to have laboratory
        $this->subject->update(['type' => 'lecture_lab']);

        $lectureActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'type' => 'lecture',
            'name' => 'Lecture Quiz 1'
        ]);

        $labActivity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'type' => 'lab',
            'name' => 'Lab Exercise 1'
        ]);

        $response = $this->get(route('grades.matrix', $this->subject));

        $response->assertSee('Lecture');
        $response->assertSee('Laboratory');
        $response->assertSee('Lecture Quiz 1');
        $response->assertSee('Lab Exercise 1');
    }
}
