<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\StudentMapping;
use App\Services\StudentMappingService;
use App\Services\GoogleClassroomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class MappingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $faculty;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faculty = Faculty::factory()->create();
        $this->subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id
        ]);
        
        // Mock authentication
        $this->actingAs($this->faculty, 'faculty');
    }

    public function test_index_displays_mappings()
    {
        // Create some test mappings
        $mapping1 = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'student_name' => 'John Doe',
            'mapping_confidence' => 0.95
        ]);

        $mapping2 = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'student_name' => 'Jane Smith',
            'mapping_confidence' => 0.6
        ]);

        $response = $this->get(route('mappings.index', $this->subject));

        $response->assertStatus(200);
        $response->assertViewIs('mappings.index');
        $response->assertViewHas('subject', $this->subject);
        $response->assertViewHas('mappings');
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
    }

    public function test_auto_match_without_gcr_class_redirects_with_error()
    {
        $this->subject->update(['gcr_class_id' => null]);

        $response = $this->get(route('mappings.auto-match', $this->subject));

        $response->assertRedirect(route('mappings.index', $this->subject));
        $response->assertSessionHas('error', 'No Google Classroom connected to this subject.');
    }

    public function test_auto_match_with_gcr_class_shows_results()
    {
        $this->subject->update(['gcr_class_id' => 'test_class_123']);

        // Mock the GoogleClassroomService
        $mockClassroomService = Mockery::mock(GoogleClassroomService::class);
        $mockClassroomService->shouldReceive('getStudents')
            ->with('test_class_123')
            ->andReturn([
                [
                    'userId' => 'gcr_123',
                    'profile' => ['name' => ['fullName' => 'John Doe']],
                    'emailAddress' => 'john.doe@gmail.com'
                ]
            ]);

        $this->app->instance(GoogleClassroomService::class, $mockClassroomService);

        // Mock the StudentMappingService
        $mockMappingService = Mockery::mock(StudentMappingService::class);
        $mockMappingService->shouldReceive('autoMatchStudents')
            ->andReturn([
                'matches' => [
                    [
                        'gcr_student' => [
                            'userId' => 'gcr_123',
                            'profile' => ['name' => ['fullName' => 'John Doe']],
                            'emailAddress' => 'john.doe@gmail.com'
                        ],
                        'school_student' => (object) ['id' => 1, 'full_name' => 'John Doe'],
                        'confidence' => 0.95,
                        'match_type' => 'email_exact'
                    ]
                ],
                'conflicts' => [],
                'unmatched' => []
            ]);

        $this->app->instance(StudentMappingService::class, $mockMappingService);

        $response = $this->get(route('mappings.auto-match', $this->subject));

        $response->assertStatus(200);
        $response->assertViewIs('mappings.auto-match');
        $response->assertViewHas('subject', $this->subject);
        $response->assertViewHas('results');
        $response->assertSee('John Doe');
    }

    public function test_save_auto_matches()
    {
        $matches = [
            [
                'gcr_student' => [
                    'userId' => 'gcr_123',
                    'profile' => ['name' => ['fullName' => 'John Doe']],
                    'emailAddress' => 'john.doe@gmail.com'
                ],
                'school_student' => (object) ['id' => 1],
                'confidence' => 0.95
            ]
        ];

        // Mock the StudentMappingService
        $mockMappingService = Mockery::mock(StudentMappingService::class);
        $mockMappingService->shouldReceive('saveMatches')
            ->with($this->subject, Mockery::any())
            ->once();

        $this->app->instance(StudentMappingService::class, $mockMappingService);

        $response = $this->post(route('mappings.save-auto-matches', $this->subject), [
            'matches' => $matches
        ]);

        $response->assertRedirect(route('mappings.index', $this->subject));
        $response->assertSessionHas('success', 'Automatic matches saved successfully.');
    }

    public function test_store_creates_manual_mapping()
    {
        $gcrStudent = [
            'userId' => 'gcr_manual',
            'profile' => ['name' => ['fullName' => 'Manual Student']],
            'emailAddress' => 'manual@gmail.com'
        ];

        $mapping = StudentMapping::factory()->make([
            'subject_id' => $this->subject->id,
            'gcr_student_id' => 'gcr_manual',
            'school_student_id' => 5,
            'student_name' => 'Manual Student',
            'student_email' => 'manual@gmail.com',
            'mapping_confidence' => 1.0
        ]);

        // Mock the StudentMappingService
        $mockMappingService = Mockery::mock(StudentMappingService::class);
        $mockMappingService->shouldReceive('createManualMapping')
            ->with($this->subject, $gcrStudent, 5)
            ->andReturn($mapping);

        $this->app->instance(StudentMappingService::class, $mockMappingService);

        $response = $this->postJson(route('mappings.store', $this->subject), [
            'gcr_student' => $gcrStudent,
            'school_student_id' => 5
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Mapping created successfully.'
        ]);
    }

    public function test_update_mapping()
    {
        $mapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'school_student_id' => 1
        ]);

        $response = $this->putJson(route('mappings.update', [$this->subject, $mapping]), [
            'school_student_id' => 2
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Mapping updated successfully.'
        ]);

        $mapping->refresh();
        $this->assertEquals(2, $mapping->school_student_id);
        $this->assertEquals(1.0, $mapping->mapping_confidence); // Manual update should set full confidence
    }

    public function test_destroy_mapping()
    {
        $mapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        // Mock the StudentMappingService
        $mockMappingService = Mockery::mock(StudentMappingService::class);
        $mockMappingService->shouldReceive('deleteMapping')
            ->with($mapping->id)
            ->andReturn(true);

        $this->app->instance(StudentMappingService::class, $mockMappingService);

        $response = $this->deleteJson(route('mappings.destroy', [$this->subject, $mapping]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Mapping deleted successfully.'
        ]);
    }

    public function test_conflicts_view()
    {
        $conflicts = [
            [
                'mapping' => StudentMapping::factory()->make([
                    'mapping_confidence' => 0.6
                ]),
                'reason' => 'low_confidence',
                'confidence' => 0.6
            ]
        ];

        // Mock the StudentMappingService
        $mockMappingService = Mockery::mock(StudentMappingService::class);
        $mockMappingService->shouldReceive('getMappingConflicts')
            ->with($this->subject)
            ->andReturn($conflicts);

        $this->app->instance(StudentMappingService::class, $mockMappingService);

        $response = $this->get(route('mappings.conflicts', $this->subject));

        $response->assertStatus(200);
        $response->assertViewIs('mappings.conflicts');
        $response->assertViewHas('subject', $this->subject);
        $response->assertViewHas('conflicts', $conflicts);
    }

    public function test_resolve_conflict()
    {
        $keepMapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $removeMapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        // Mock the StudentMappingService
        $mockMappingService = Mockery::mock(StudentMappingService::class);
        $mockMappingService->shouldReceive('resolveConflict')
            ->with($keepMapping->id, [$removeMapping->id])
            ->andReturn(true);

        $this->app->instance(StudentMappingService::class, $mockMappingService);

        $response = $this->postJson(route('mappings.resolve-conflict', $this->subject), [
            'keep_mapping_id' => $keepMapping->id,
            'remove_mapping_ids' => [$removeMapping->id]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Conflict resolved successfully.'
        ]);
    }

    public function test_get_school_students()
    {
        $response = $this->getJson(route('mappings.school-students', $this->subject));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'students' => [
                ['id' => 1, 'full_name' => 'John Doe', 'email' => 'john.doe@student.lorma.edu'],
                ['id' => 2, 'full_name' => 'Jane Smith', 'email' => 'jane.smith@student.lorma.edu'],
            ]
        ]);
    }

    public function test_validation_errors_for_store()
    {
        $response = $this->postJson(route('mappings.store', $this->subject), [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gcr_student']);
    }

    public function test_validation_errors_for_update()
    {
        $mapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $response = $this->putJson(route('mappings.update', [$this->subject, $mapping]), [
            'school_student_id' => 'invalid_id'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['school_student_id']);
    }

    public function test_validation_errors_for_resolve_conflict()
    {
        $response = $this->postJson(route('mappings.resolve-conflict', $this->subject), [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['keep_mapping_id', 'remove_mapping_ids']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}