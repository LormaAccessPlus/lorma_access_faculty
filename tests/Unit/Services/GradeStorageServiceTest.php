<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GradeStorageService;
use App\Models\Subject;
use App\Models\StudentMapping;
use App\Models\FinalRating;
use App\Models\TermGrade;
use App\Models\SchoolTermGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class GradeStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradeStorageService $service;
    private Subject $subject;
    private StudentMapping $studentMapping;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new GradeStorageService();
        
        // Create test data
        $this->subject = Subject::factory()->create([
            'subject_code' => 'TESTSUBJ',
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);
        
        $this->studentMapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'school_student_id' => '1000001',
            'student_name' => 'Test Student'
        ]);
    }

    public function test_validates_final_rating_data()
    {
        $finalRating = new FinalRating([
            'student_mapping_id' => null, // Invalid - null
            'subject_id' => $this->subject->id,
            'prelim_grade' => 150, // Invalid - over 100
            'midterm_grade' => -10, // Invalid - negative
            'finals_grade' => 85,
            'final_rating' => 75
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Validation failed');

        // Use reflection to call private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateFinalRating');
        $method->setAccessible(true);
        $method->invoke($this->service, $finalRating);
    }

    public function test_validates_final_rating_data_success()
    {
        $finalRating = new FinalRating([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'prelim_grade' => 85,
            'midterm_grade' => 88,
            'finals_grade' => 90,
            'final_rating' => 87.8
        ]);

        // Use reflection to call private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateFinalRating');
        $method->setAccessible(true);
        
        // Should not throw exception
        $method->invoke($this->service, $finalRating);
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function test_generates_code_number_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateCodeNumber');
        $method->setAccessible(true);
        
        $codeNumber = $method->invoke($this->service, $this->subject, '1', '2024-2025');
        
        $this->assertEquals('TESTSUBJ-1-2024', $codeNumber);
    }

    public function test_converts_semester_to_term()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertSemesterToTerm');
        $method->setAccessible(true);
        
        $this->assertEquals('1st', $method->invoke($this->service, '1'));
        $this->assertEquals('2nd', $method->invoke($this->service, '2'));
        $this->assertEquals('Summer', $method->invoke($this->service, '3'));
        $this->assertEquals('1st', $method->invoke($this->service, 'first'));
        $this->assertEquals('2nd', $method->invoke($this->service, 'second'));
        $this->assertEquals('Summer', $method->invoke($this->service, 'summer'));
    }

    public function test_formats_grade_for_school_db()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatGradeForSchoolDB');
        $method->setAccessible(true);
        
        $this->assertEquals('85.50', $method->invoke($this->service, 85.5));
        $this->assertEquals('90.00', $method->invoke($this->service, 90));
        $this->assertNull($method->invoke($this->service, null));
    }

    public function test_compares_grades_between_databases()
    {
        $finalRating = new FinalRating([
            'prelim_grade' => 85.5,
            'midterm_grade' => 88.0,
            'finals_grade' => 90.25
        ]);

        $schoolGrade = (object) [
            'PrelimGrade' => '85.50',
            'MidtermGrade' => '87.00', // Different
            'FinalsGrade' => '90.25'
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('compareGrades');
        $method->setAccessible(true);
        
        $discrepancies = $method->invoke($this->service, $finalRating, $schoolGrade);
        
        $this->assertCount(1, $discrepancies);
        $this->assertEquals('midterm_grade', $discrepancies[0]['field']);
        $this->assertEquals('88.00', $discrepancies[0]['app_value']);
        $this->assertEquals('87.00', $discrepancies[0]['school_value']);
    }

    public function test_gets_grade_storage_statistics()
    {
        // Create test data
        FinalRating::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        $stats = $this->service->getGradeStorageStatistics($this->subject, '2024-2025', '1');

        $this->assertArrayHasKey('total_students', $stats);
        $this->assertArrayHasKey('grades_computed', $stats);
        $this->assertArrayHasKey('grades_synced', $stats);
        $this->assertArrayHasKey('pending_sync', $stats);
        $this->assertArrayHasKey('sync_percentage', $stats);
        
        $this->assertEquals(1, $stats['total_students']);
        $this->assertEquals(1, $stats['grades_computed']);
    }

    public function test_tests_database_connections()
    {
        $connections = $this->service->testDatabaseConnections();

        $this->assertArrayHasKey('app_db', $connections);
        $this->assertArrayHasKey('school_db', $connections);
        
        $this->assertArrayHasKey('connected', $connections['app_db']);
        $this->assertArrayHasKey('error', $connections['app_db']);
        
        $this->assertArrayHasKey('connected', $connections['school_db']);
        $this->assertArrayHasKey('error', $connections['school_db']);
        
        // App DB should be connected in tests
        $this->assertTrue($connections['app_db']['connected']);
    }

    public function test_sync_grades_to_school_database_with_no_final_ratings()
    {
        $results = $this->service->syncGradesToSchoolDatabase($this->subject, '2024-2025', '1');

        $this->assertFalse($results['success']);
        $this->assertContains('No final ratings found for synchronization', $results['errors']);
    }

    public function test_sync_grades_to_school_database_with_final_ratings()
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

        // Mock the school database connection to avoid actual database operations
        DB::shouldReceive('connection')
            ->with('school_db')
            ->andReturnSelf();
        
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        
        DB::shouldReceive('table')
            ->with('termgrades')
            ->andReturnSelf();
        
        DB::shouldReceive('updateOrInsert')
            ->once()
            ->andReturn(true);

        $results = $this->service->syncGradesToSchoolDatabase($this->subject, '2024-2025', '1');

        $this->assertTrue($results['success']);
        $this->assertEquals(1, $results['stored_count']);
        $this->assertEquals(0, $results['failed_count']);
    }

    public function test_store_grades_with_sync_handles_validation_errors()
    {
        // Create invalid final rating
        $finalRating = new FinalRating([
            'student_mapping_id' => null, // Invalid
            'subject_id' => $this->subject->id,
            'prelim_grade' => 85,
            'midterm_grade' => 88,
            'finals_grade' => 90,
            'final_rating' => 87.8
        ]);

        $results = $this->service->storeGradesWithSync($this->subject, [$finalRating], '2024-2025', '1');

        $this->assertFalse($results['success']);
        $this->assertEquals(0, $results['stored_count']);
        $this->assertEquals(1, $results['failed_count']);
        $this->assertNotEmpty($results['errors']);
    }

    public function test_verify_grade_synchronization_with_no_app_grades()
    {
        $verification = $this->service->verifyGradeSynchronization($this->subject, '2024-2025', '1');

        $this->assertTrue($verification['synchronized']); // No grades to sync
        $this->assertEquals(0, $verification['app_db_count']);
        $this->assertEquals(0, $verification['school_db_count']);
        $this->assertEmpty($verification['discrepancies']);
        $this->assertEmpty($verification['missing_in_school_db']);
    }

    public function test_verify_grade_synchronization_with_missing_school_grades()
    {
        // Create final rating in app database
        FinalRating::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        // Mock school database to return empty results
        DB::shouldReceive('connection')
            ->with('school_db')
            ->andReturnSelf();
        
        DB::shouldReceive('table')
            ->with('termgrades')
            ->andReturnSelf();
        
        DB::shouldReceive('where')
            ->andReturnSelf();
        
        DB::shouldReceive('get')
            ->andReturn(collect([]));
        
        DB::shouldReceive('keyBy')
            ->andReturn(collect([]));

        $verification = $this->service->verifyGradeSynchronization($this->subject, '2024-2025', '1');

        $this->assertFalse($verification['synchronized']);
        $this->assertEquals(1, $verification['app_db_count']);
        $this->assertEquals(0, $verification['school_db_count']);
        $this->assertCount(1, $verification['missing_in_school_db']);
    }

    public function test_handles_database_transaction_rollback_on_error()
    {
        $finalRating = FinalRating::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        // Mock database to throw exception during transaction
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('connection')
            ->with('school_db')
            ->andReturnSelf();
        DB::shouldReceive('beginTransaction')->once();
        
        // Simulate error during processing
        DB::shouldReceive('rollBack')->twice(); // Both connections should rollback
        
        // Mock the table operation to throw exception
        DB::shouldReceive('table')
            ->with('termgrades')
            ->andThrow(new Exception('Database error'));

        $results = $this->service->storeGradesWithSync($this->subject, [$finalRating], '2024-2025', '1');

        $this->assertFalse($results['success']);
        $this->assertNotEmpty($results['errors']);
    }

    public function test_logs_successful_grade_storage()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Grade successfully stored in school database', \Mockery::type('array'));

        $finalRating = FinalRating::factory()->create([
            'student_mapping_id' => $this->studentMapping->id,
            'subject_id' => $this->subject->id,
            'academic_year' => '2024-2025',
            'semester' => '1'
        ]);

        // Mock successful database operations
        DB::shouldReceive('connection')->with('school_db')->andReturnSelf();
        DB::shouldReceive('beginTransaction')->twice();
        DB::shouldReceive('commit')->twice();
        DB::shouldReceive('table')->with('termgrades')->andReturnSelf();
        DB::shouldReceive('updateOrInsert')->once()->andReturn(true);

        $this->service->storeGradesWithSync($this->subject, [$finalRating], '2024-2025', '1');
    }

    public function test_logs_grade_storage_errors()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to store grade for student', \Mockery::type('array'));

        // Create invalid final rating to trigger error
        $finalRating = new FinalRating([
            'student_mapping_id' => 999999, // Non-existent mapping
            'subject_id' => $this->subject->id,
            'prelim_grade' => 85,
            'midterm_grade' => 88,
            'finals_grade' => 90,
            'final_rating' => 87.8
        ]);

        $results = $this->service->storeGradesWithSync($this->subject, [$finalRating], '2024-2025', '1');

        $this->assertFalse($results['success']);
    }
}