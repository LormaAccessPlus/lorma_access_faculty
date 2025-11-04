<?php

namespace Tests\Unit;

use App\Models\Faculty;
use App\Models\Subject;
use App\Services\SubjectService;
use App\Services\SchoolDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

class SubjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubjectService $subjectService;
    private $mockSchoolDatabaseService;
    private Faculty $faculty;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSchoolDatabaseService = Mockery::mock(SchoolDatabaseService::class);
        $this->subjectService = new SubjectService($this->mockSchoolDatabaseService);
        $this->faculty = Faculty::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_current_semester_subjects_returns_current_subjects(): void
    {
        // Create current semester subjects
        $currentSubjects = Subject::factory()->count(3)->create([
            'faculty_id' => $this->faculty->id,
            'academic_year' => '2024-2025',
            'semester' => '1st Semester'
        ]);

        // Create past semester subjects
        Subject::factory()->count(2)->create([
            'faculty_id' => $this->faculty->id,
            'academic_year' => '2023-2024',
            'semester' => '2nd Semester'
        ]);

        // Mock Carbon to return a date in the first semester
        Carbon::setTestNow(Carbon::create(2024, 9, 15)); // September 2024

        $result = $this->subjectService->getCurrentSemesterSubjects($this->faculty->id);

        $this->assertCount(3, $result);
        $this->assertEquals('2024-2025', $result->first()->academic_year);
        $this->assertEquals('1st Semester', $result->first()->semester);
    }

    public function test_get_past_semester_subjects_returns_historical_subjects(): void
    {
        // Create current semester subjects
        Subject::factory()->count(2)->create([
            'faculty_id' => $this->faculty->id,
            'academic_year' => '2024-2025',
            'semester' => '1st Semester'
        ]);

        // Create past semester subjects
        $pastSubjects = Subject::factory()->count(3)->create([
            'faculty_id' => $this->faculty->id,
            'academic_year' => '2023-2024',
            'semester' => '2nd Semester'
        ]);

        // Mock Carbon to return a date in the first semester
        Carbon::setTestNow(Carbon::create(2024, 9, 15)); // September 2024

        $result = $this->subjectService->getPastSemesterSubjects($this->faculty->id);

        $this->assertNotEmpty($result);
        $this->assertTrue($result->has('2023-2024'));
        $this->assertTrue($result['2023-2024']->has('2nd Semester'));
        $this->assertCount(3, $result['2023-2024']['2nd Semester']);
    }

    public function test_configure_subject_type_updates_subject_type(): void
    {
        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'type' => 'lecture_only'
        ]);

        $result = $this->subjectService->configureSubjectType($subject, 'lecture_lab');

        $this->assertEquals('lecture_lab', $result->type);
        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'type' => 'lecture_lab'
        ]);
    }

    public function test_configure_subject_type_throws_exception_for_invalid_type(): void
    {
        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'type' => 'lecture_only'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid subject type');

        $this->subjectService->configureSubjectType($subject, 'invalid_type');
    }

    public function test_get_subject_metadata_returns_correct_data(): void
    {
        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id
        ]);

        // Create some activities
        $subject->activities()->create([
            'name' => 'Quiz 1',
            'type' => 'lecture',
            'term' => 'prelim',
            'max_score' => 50
        ]);

        $subject->activities()->create([
            'name' => 'Lab 1',
            'type' => 'lab',
            'term' => 'prelim',
            'max_score' => 100
        ]);

        // Create some student mappings
        $subject->studentMappings()->create([
            'school_student_id' => 1,
            'student_name' => 'John Doe',
            'student_email' => 'john@example.com'
        ]);

        $metadata = $this->subjectService->getSubjectMetadata($subject);

        $this->assertEquals(1, $metadata['lecture_activities']);
        $this->assertEquals(1, $metadata['lab_activities']);
        $this->assertEquals(2, $metadata['total_activities']);
        $this->assertEquals(1, $metadata['student_count']);
        $this->assertFalse($metadata['has_gcr_integration']);
        $this->assertArrayHasKey('grade_progress', $metadata);
    }

    public function test_sync_subjects_from_school_database_creates_new_subjects(): void
    {
        $this->faculty->update(['school_faculty_id' => 123]);

        $mockAssignments = collect([
            (object) [
                'id' => 1,
                'subject_code' => 'CS101',
                'subject_name' => 'Introduction to Computer Science',
                'section' => 'A',
                'academic_year' => '2024-2025',
                'semester' => '1st Semester'
            ],
            (object) [
                'id' => 2,
                'subject_code' => 'CS102',
                'subject_name' => 'Data Structures',
                'section' => 'B',
                'academic_year' => '2024-2025',
                'semester' => '1st Semester'
            ]
        ]);

        $this->mockSchoolDatabaseService
            ->shouldReceive('getFacultyAssignments')
            ->with(123)
            ->once()
            ->andReturn($mockAssignments);

        $syncedCount = $this->subjectService->syncSubjectsFromSchoolDatabase($this->faculty->id);

        $this->assertEquals(2, $syncedCount);
        $this->assertDatabaseHas('subjects', [
            'school_subject_id' => 1,
            'faculty_id' => $this->faculty->id,
            'subject_code' => 'CS101'
        ]);
        $this->assertDatabaseHas('subjects', [
            'school_subject_id' => 2,
            'faculty_id' => $this->faculty->id,
            'subject_code' => 'CS102'
        ]);
    }

    public function test_sync_subjects_does_not_duplicate_existing_subjects(): void
    {
        $this->faculty->update(['school_faculty_id' => 123]);

        // Create existing subject
        Subject::factory()->create([
            'school_subject_id' => 1,
            'faculty_id' => $this->faculty->id,
            'subject_code' => 'CS101'
        ]);

        $mockAssignments = collect([
            (object) [
                'id' => 1,
                'subject_code' => 'CS101',
                'subject_name' => 'Introduction to Computer Science',
                'section' => 'A',
                'academic_year' => '2024-2025',
                'semester' => '1st Semester'
            ]
        ]);

        $this->mockSchoolDatabaseService
            ->shouldReceive('getFacultyAssignments')
            ->with(123)
            ->once()
            ->andReturn($mockAssignments);

        $syncedCount = $this->subjectService->syncSubjectsFromSchoolDatabase($this->faculty->id);

        $this->assertEquals(0, $syncedCount);
        $this->assertEquals(1, Subject::where('school_subject_id', 1)->count());
    }

    public function test_sync_subjects_throws_exception_when_faculty_not_linked(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Faculty member is not linked to school database');

        $this->subjectService->syncSubjectsFromSchoolDatabase($this->faculty->id);
    }
}
