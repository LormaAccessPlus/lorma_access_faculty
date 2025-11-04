<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\StudentMapping;
use App\Models\FinalRating;
use App\Services\GradeStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class GradeSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $faculty;
    private Subject $subject;
    private StudentMapping $studentMapping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Faculty::factory()->create();
        $this->subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);
        $this->studentMapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $this->actingAs($this->faculty, 'faculty');
    }

    public function test_displays_grade_synchronization_interface()
    {
        $response = $this->get(route('grades.sync.index', $this->subject));

        $response->assertStatus(200);
        $response->assertViewIs('grades.sync');
        $response->assertViewHas('subject', $this->subject);
        $response->assertViewHas('stats');
        $response->assertViewHas('verification');
        $response->assertViewHas('connectionStatus');
    }

    public function test_synchronizes_grades_successfully()
    {
        // Create final rating
        FinalRating::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        // Mock the grade storage service
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('syncGradesToSchoolDatabase')
                ->once()
                ->andReturn([
                    'success' => true,
                    'stored_count' => 1,
                    'failed_count' => 0,
                    'errors' => []
                ]);
        });

        $response = $this->postJson(route('grades.sync.store', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1',
            'confirm' => true
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Successfully synchronized 1 grade records to school database.'
        ]);
    }

    public function test_synchronization_fails_with_validation_errors()
    {
        $response = $this->postJson(route('grades.sync.store', $this->subject), [
            'academic_year' => '', // Missing required field
            'semester' => '1',
            'confirm' => true
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['academic_year']);
    }

    public function test_synchronization_requires_confirmation()
    {
        $response = $this->postJson(route('grades.sync.store', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1',
            'confirm' => false // Not confirmed
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['confirm']);
    }

    public function test_synchronization_handles_service_errors()
    {
        // Mock the grade storage service to throw exception
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('syncGradesToSchoolDatabase')
                ->once()
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->postJson(route('grades.sync.store', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1',
            'confirm' => true
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Grade synchronization failed: Database connection failed'
        ]);
    }

    public function test_synchronization_handles_partial_failures()
    {
        // Mock the grade storage service to return partial failure
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('syncGradesToSchoolDatabase')
                ->once()
                ->andReturn([
                    'success' => false,
                    'stored_count' => 2,
                    'failed_count' => 1,
                    'errors' => [
                        ['student_mapping_id' => 1, 'error' => 'Validation failed']
                    ]
                ]);
        });

        $response = $this->postJson(route('grades.sync.store', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1',
            'confirm' => true
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Grade synchronization completed with errors.'
        ]);
    }

    public function test_verifies_grade_synchronization()
    {
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('verifyGradeSynchronization')
                ->once()
                ->andReturn([
                    'synchronized' => true,
                    'discrepancies' => [],
                    'missing_in_school_db' => [],
                    'app_db_count' => 1,
                    'school_db_count' => 1
                ]);
        });

        $response = $this->postJson(route('grades.sync.verify', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'synchronized' => true,
                'app_db_count' => 1,
                'school_db_count' => 1
            ]
        ]);
    }

    public function test_verification_handles_service_errors()
    {
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('verifyGradeSynchronization')
                ->once()
                ->andThrow(new \Exception('Verification failed'));
        });

        $response = $this->postJson(route('grades.sync.verify', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Grade verification failed: Verification failed'
        ]);
    }

    public function test_gets_grade_statistics()
    {
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('getGradeStorageStatistics')
                ->once()
                ->andReturn([
                    'total_students' => 5,
                    'grades_computed' => 4,
                    'grades_synced' => 3,
                    'pending_sync' => 1,
                    'sync_percentage' => 75.0
                ]);
        });

        $response = $this->postJson(route('grades.sync.statistics', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'total_students' => 5,
                'grades_computed' => 4,
                'grades_synced' => 3,
                'pending_sync' => 1,
                'sync_percentage' => 75.0
            ]
        ]);
    }

    public function test_statistics_handles_service_errors()
    {
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('getGradeStorageStatistics')
                ->once()
                ->andThrow(new \Exception('Statistics failed'));
        });

        $response = $this->postJson(route('grades.sync.statistics', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to get statistics: Statistics failed'
        ]);
    }

    public function test_tests_database_connections()
    {
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('testDatabaseConnections')
                ->once()
                ->andReturn([
                    'app_db' => ['connected' => true, 'error' => null],
                    'school_db' => ['connected' => false, 'error' => 'Connection refused']
                ]);
        });

        $response = $this->postJson(route('grades.sync.test-connections'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'app_db' => ['connected' => true],
                'school_db' => ['connected' => false, 'error' => 'Connection refused']
            ]
        ]);
    }

    public function test_connection_test_handles_service_errors()
    {
        $this->mock(GradeStorageService::class, function ($mock) {
            $mock->shouldReceive('testDatabaseConnections')
                ->once()
                ->andThrow(new \Exception('Connection test failed'));
        });

        $response = $this->postJson(route('grades.sync.test-connections'));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Connection test failed: Connection test failed'
        ]);
    }

    public function test_previews_grades_before_synchronization()
    {
        // Create final rating
        $finalRating = FinalRating::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'academic_year' => '2024-2025',
            'semester' => '1',
            'prelim_grade' => 85,
            'midterm_grade' => 88,
            'finals_grade' => 90,
            'final_rating' => 87.8
        ]);

        $response = $this->postJson(route('grades.sync.preview', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'count' => 1,
                'grades' => [
                    [
                        'student_name' => $this->studentMapping->student_name,
                        'school_student_id' => $this->studentMapping->school_student_id,
                        'prelim_grade' => 85,
                        'midterm_grade' => 88,
                        'finals_grade' => 90,
                        'final_rating' => 87.8
                    ]
                ]
            ]
        ]);
    }

    public function test_preview_handles_no_grades()
    {
        $response = $this->postJson(route('grades.sync.preview', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'count' => 0,
                'grades' => []
            ]
        ]);
    }

    public function test_preview_handles_service_errors()
    {
        // Mock FinalRating query to throw exception
        $this->mock(\App\Models\FinalRating::class, function ($mock) {
            $mock->shouldReceive('with')
                ->andReturnSelf();
            $mock->shouldReceive('where')
                ->andReturnSelf();
            $mock->shouldReceive('get')
                ->andThrow(new \Exception('Database error'));
        });

        $response = $this->postJson(route('grades.sync.preview', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false
        ]);
    }

    public function test_requires_authentication()
    {
        auth()->logout();

        $response = $this->get(route('grades.sync.index', $this->subject));
        $response->assertRedirect(route('auth.login'));

        $response = $this->postJson(route('grades.sync.store', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1',
            'confirm' => true
        ]);
        $response->assertStatus(302); // Redirect to login for unauthenticated requests
    }

    public function test_validates_request_parameters()
    {
        // Test missing academic_year
        $response = $this->postJson(route('grades.sync.verify', $this->subject), [
            'semester' => '1'
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['academic_year']);

        // Test missing semester
        $response = $this->postJson(route('grades.sync.statistics', $this->subject), [
            'academic_year' => '2024-2025'
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['semester']);

        // Test invalid data types
        $response = $this->postJson(route('grades.sync.preview', $this->subject), [
            'academic_year' => 123, // Should be string
            'semester' => true // Should be string
        ]);
        $response->assertStatus(422);
    }

    public function test_handles_large_datasets()
    {
        // Create multiple student mappings and final ratings
        $studentMappings = StudentMapping::factory()->count(50)->create([
            'subject_id' => $this->subject->id
        ]);

        foreach ($studentMappings as $mapping) {
            FinalRating::factory()->create([
                'student_mapping_id' => $mapping->id,
                'subject_id' => $this->subject->id,
                'academic_year' => '2024-2025',
                'semester' => '1'
            ]);
        }

        $response = $this->postJson(route('grades.sync.preview', $this->subject), [
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'count' => 50
            ]
        ]);

        // Verify the response contains all grades
        $responseData = $response->json();
        $this->assertCount(50, $responseData['data']['grades']);
    }
}