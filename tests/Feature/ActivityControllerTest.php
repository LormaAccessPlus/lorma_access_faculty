<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Faculty $faculty;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faculty = Faculty::factory()->create([
            'email' => 'test@lorma.edu' // Ensure valid domain
        ]);
        $this->subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'type' => 'lecture_lab'
        ]);
        
        // Authenticate using the faculty guard
        $this->actingAs($this->faculty, 'faculty');
    }

    public function test_can_create_activity(): void
    {
        $activityData = [
            'subject_id' => $this->subject->id,
            'name' => 'Quiz 1',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 50.00,
            'weight' => 10.00
        ];

        $response = $this->postJson('/activities', $activityData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Activity created successfully'
            ]);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $this->subject->id,
            'name' => 'Quiz 1',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 50.00,
            'weight' => 10.00
        ]);
    }

    public function test_cannot_create_activity_for_other_faculty_subject(): void
    {
        $otherFaculty = Faculty::factory()->create();
        $otherSubject = Subject::factory()->create(['faculty_id' => $otherFaculty->id]);

        $activityData = [
            'subject_id' => $otherSubject->id,
            'name' => 'Quiz 1',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 50.00
        ];

        $response = $this->postJson('/activities', $activityData);

        $response->assertStatus(403);
    }

    public function test_can_update_activity(): void
    {
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Original Quiz',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 50.00
        ]);

        $updateData = [
            'name' => 'Updated Quiz',
            'type' => 'lab',
            'term' => 'midterm',
            'max_score' => 75.00,
            'weight' => 15.00
        ];

        $response = $this->putJson("/activities/{$activity->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Activity updated successfully'
            ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'name' => 'Updated Quiz',
            'type' => 'lab',
            'term' => 'midterm',
            'max_score' => 75.00,
            'weight' => 15.00
        ]);
    }

    public function test_cannot_update_other_faculty_activity(): void
    {
        $otherFaculty = Faculty::factory()->create();
        $otherSubject = Subject::factory()->create(['faculty_id' => $otherFaculty->id]);
        $activity = Activity::factory()->create(['subject_id' => $otherSubject->id]);

        $updateData = [
            'name' => 'Updated Quiz',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 75.00
        ];

        $response = $this->putJson("/activities/{$activity->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_can_delete_activity(): void
    {
        $activity = Activity::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $response = $this->deleteJson("/activities/{$activity->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Activity deleted successfully'
            ]);

        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id
        ]);
    }

    public function test_cannot_delete_other_faculty_activity(): void
    {
        $otherFaculty = Faculty::factory()->create();
        $otherSubject = Subject::factory()->create(['faculty_id' => $otherFaculty->id]);
        $activity = Activity::factory()->create(['subject_id' => $otherSubject->id]);

        $response = $this->deleteJson("/activities/{$activity->id}");

        $response->assertStatus(403);
    }

    public function test_can_get_organized_activities(): void
    {
        // Create activities for different terms and types
        Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Prelim Lecture Quiz',
            'type' => 'lecture',
            'term' => 'prelim'
        ]);

        Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Prelim Lab Exercise',
            'type' => 'lab',
            'term' => 'prelim'
        ]);

        Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'name' => 'Midterm Lecture Quiz',
            'type' => 'lecture',
            'term' => 'midterm'
        ]);

        $response = $this->getJson("/api/subjects/{$this->subject->id}/activities/organized");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'subject',
                'activities' => [
                    'prelim' => [
                        'lecture',
                        'lab'
                    ],
                    'midterm' => [
                        'lecture',
                        'lab'
                    ],
                    'finals' => [
                        'lecture',
                        'lab'
                    ]
                ]
            ]);

        $data = $response->json();
        
        // Check that activities are properly organized
        $this->assertCount(1, $data['activities']['prelim']['lecture']);
        $this->assertCount(1, $data['activities']['prelim']['lab']);
        $this->assertCount(1, $data['activities']['midterm']['lecture']);
        $this->assertCount(0, $data['activities']['midterm']['lab']);
        $this->assertEquals('Prelim Lecture Quiz', $data['activities']['prelim']['lecture'][0]['name']);
        $this->assertEquals('Prelim Lab Exercise', $data['activities']['prelim']['lab'][0]['name']);
    }

    public function test_cannot_get_organized_activities_for_other_faculty_subject(): void
    {
        $otherFaculty = Faculty::factory()->create();
        $otherSubject = Subject::factory()->create(['faculty_id' => $otherFaculty->id]);

        $response = $this->getJson("/api/subjects/{$otherSubject->id}/activities/organized");

        $response->assertStatus(403);
    }

    public function test_can_bulk_create_activities_from_gcr(): void
    {
        $activitiesData = [
            'subject_id' => $this->subject->id,
            'activities' => [
                [
                    'gcr_assignment_id' => 'gcr_123',
                    'name' => 'GCR Assignment 1',
                    'type' => 'lecture',
                    'term' => 'prelim',
                    'max_score' => 100.00,
                    'weight' => 20.00
                ],
                [
                    'gcr_assignment_id' => 'gcr_456',
                    'name' => 'GCR Assignment 2',
                    'type' => 'lab',
                    'term' => 'prelim',
                    'max_score' => 50.00
                ]
            ]
        ];

        $response = $this->postJson('/activities/bulk-create-gcr', $activitiesData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '2 activities created successfully'
            ]);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $this->subject->id,
            'gcr_assignment_id' => 'gcr_123',
            'name' => 'GCR Assignment 1'
        ]);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $this->subject->id,
            'gcr_assignment_id' => 'gcr_456',
            'name' => 'GCR Assignment 2'
        ]);
    }

    public function test_bulk_create_skips_existing_gcr_activities(): void
    {
        // Create existing activity with GCR ID
        Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'gcr_assignment_id' => 'gcr_123'
        ]);

        $activitiesData = [
            'subject_id' => $this->subject->id,
            'activities' => [
                [
                    'gcr_assignment_id' => 'gcr_123', // Existing
                    'name' => 'Duplicate Assignment',
                    'type' => 'lecture',
                    'term' => 'prelim',
                    'max_score' => 100.00
                ],
                [
                    'gcr_assignment_id' => 'gcr_456', // New
                    'name' => 'New Assignment',
                    'type' => 'lecture',
                    'term' => 'prelim',
                    'max_score' => 50.00
                ]
            ]
        ];

        $response = $this->postJson('/activities/bulk-create-gcr', $activitiesData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '1 activities created successfully'
            ]);

        // Should only create the new one
        $this->assertDatabaseHas('activities', [
            'gcr_assignment_id' => 'gcr_456',
            'name' => 'New Assignment'
        ]);

        // Should not duplicate the existing one
        $this->assertEquals(2, Activity::where('subject_id', $this->subject->id)->count());
    }

    public function test_activity_validation_rules(): void
    {
        $invalidData = [
            'subject_id' => $this->subject->id,
            'name' => '', // Required
            'type' => 'invalid_type', // Must be lecture or lab
            'term' => 'invalid_term', // Must be prelim, midterm, or finals
            'max_score' => -1, // Must be positive
            'weight' => 150 // Must be <= 100
        ];

        $response = $this->postJson('/activities', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'term', 'max_score', 'weight']);
    }

    public function test_can_filter_activities_by_parameters(): void
    {
        Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'type' => 'lecture',
            'term' => 'prelim'
        ]);

        Activity::factory()->create([
            'subject_id' => $this->subject->id,
            'type' => 'lab',
            'term' => 'midterm'
        ]);

        // Filter by type
        $response = $this->getJson('/activities?subject_id=' . $this->subject->id . '&type=lecture');
        $response->assertStatus(200);
        $activities = $response->json('activities');
        $this->assertCount(1, collect($activities)->flatten(1));

        // Filter by term
        $response = $this->getJson('/activities?subject_id=' . $this->subject->id . '&term=prelim');
        $response->assertStatus(200);
        $activities = $response->json('activities');
        $this->assertCount(1, collect($activities)->flatten(1));
    }
}
