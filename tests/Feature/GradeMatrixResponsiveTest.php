<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Activity;
use App\Models\StudentMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeMatrixResponsiveTest extends TestCase
{
    use RefreshDatabase;

    protected $faculty;
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Faculty::factory()->create();
        $this->subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'type' => 'lecture_lab'
        ]);

        $this->actingAs($this->faculty, 'faculty');
    }

    public function test_grade_matrix_handles_large_number_of_activities()
    {
        // Create many activities to test responsive design
        for ($i = 1; $i <= 15; $i++) {
            Activity::factory()->create([
                'subject_id' => $this->subject->id,
                'name' => "Activity $i",
                'type' => $i % 2 === 0 ? 'lab' : 'lecture',
                'term' => ['prelim', 'midterm', 'finals'][$i % 3]
            ]);
        }

        // Create several students
        for ($i = 1; $i <= 10; $i++) {
            StudentMapping::factory()->create([
                'subject_id' => $this->subject->id,
                'student_name' => "Student $i",
                'student_email' => "student$i@example.com"
            ]);
        }

        $response = $this->get(route('grades.matrix', $this->subject));

        $response->assertStatus(200);
        
        // Check that responsive CSS classes are present
        $response->assertSee('table-responsive');
        $response->assertSee('sticky-left');
        $response->assertSee('sticky-top');
        
        // Check that all activities are displayed
        for ($i = 1; $i <= 15; $i++) {
            $response->assertSee("Activity $i");
        }
        
        // Check that all students are displayed
        for ($i = 1; $i <= 10; $i++) {
            $response->assertSee("Student $i");
        }
    }

    public function test_grade_matrix_separates_activities_by_term_and_type()
    {
        // Create activities for different terms and types
        $activities = [
            ['name' => 'Prelim Lecture Quiz', 'type' => 'lecture', 'term' => 'prelim'],
            ['name' => 'Prelim Lab Exercise', 'type' => 'lab', 'term' => 'prelim'],
            ['name' => 'Midterm Lecture Exam', 'type' => 'lecture', 'term' => 'midterm'],
            ['name' => 'Midterm Lab Project', 'type' => 'lab', 'term' => 'midterm'],
            ['name' => 'Finals Lecture Test', 'type' => 'lecture', 'term' => 'finals'],
            ['name' => 'Finals Lab Demo', 'type' => 'lab', 'term' => 'finals'],
        ];

        foreach ($activities as $activityData) {
            Activity::factory()->create(array_merge($activityData, [
                'subject_id' => $this->subject->id
            ]));
        }

        StudentMapping::factory()->create(['subject_id' => $this->subject->id]);

        $response = $this->get(route('grades.matrix', $this->subject));

        $response->assertStatus(200);
        
        // Check term headers
        $response->assertSee('Prelim - Lecture');
        $response->assertSee('Prelim - Laboratory');
        $response->assertSee('Midterm - Lecture');
        $response->assertSee('Midterm - Laboratory');
        $response->assertSee('Finals - Lecture');
        $response->assertSee('Finals - Laboratory');
        
        // Check visual separation classes
        $response->assertSee('lecture-header');
        $response->assertSee('lab-header');
        $response->assertSee('lecture-activity');
        $response->assertSee('lab-activity');
    }

    public function test_grade_matrix_shows_max_scores_for_activities()
    {
        $activity1 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Quiz 1',
            'max_score' => 50
        ]);

        $activity2 = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Exam 1',
            'max_score' => 100
        ]);

        StudentMapping::factory()->create(['subject_id' => $this->subject->id]);

        $response = $this->get(route('grades.matrix', $this->subject));

        $response->assertStatus(200);
        $response->assertSee('/50');
        $response->assertSee('/100');
    }

    public function test_grade_matrix_includes_javascript_for_inline_editing()
    {
        Activity::factory()->create(['subject_id' => $this->subject->id]);
        StudentMapping::factory()->create(['subject_id' => $this->subject->id]);

        $response = $this->get(route('grades.matrix', $this->subject));

        $response->assertStatus(200);
        
        // Check that JavaScript functionality is included
        $response->assertSee('grade-input');
        $response->assertSee('loading-overlay');
        
        // Check that input elements have required data attributes
        $response->assertSee('data-student-mapping-id');
        $response->assertSee('data-activity-id');
        $response->assertSee('data-max-score');
        
        // Check that essential CSS classes are present
        $response->assertSee('form-control-sm');
        $response->assertSee('grade-matrix-table');
    }
}