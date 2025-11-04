<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\StudentMapping;
use App\Services\GoogleClassroomService;
use Mockery;

class ClassroomSyncControllerTest extends TestCase
{
    private $mockClassroomService;
    private Faculty $faculty;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClassroomService = Mockery::mock(GoogleClassroomService::class);
        $this->app->instance(GoogleClassroomService::class, $this->mockClassroomService);
        
        $this->faculty = new Faculty([
            'id' => 1,
            'name' => 'Test Faculty',
            'email' => 'test@lorma.edu',
            'google_id' => 'google-123'
        ]);
        
        // Mock the auth guard
        $this->app['auth']->shouldUse('faculty');
        $this->be($this->faculty, 'faculty');
    }

    public function test_index_displays_classroom_integration_page()
    {
        // Mock the Subject model to return collections
        $mockConnectedSubjects = collect([]);
        $mockUnconnectedSubjects = collect([]);
        
        Subject::shouldReceive('where')
            ->with('faculty_id', $this->faculty->id)
            ->andReturnSelf();
        Subject::shouldReceive('get')
            ->andReturn(collect([]));

        $response = $this->get('/classroom');

        $response->assertStatus(200);
        $response->assertViewIs('classroom.index');
        $response->assertViewHas('connectedSubjects');
        $response->assertViewHas('unconnectedSubjects');
    }

    public function test_fetch_courses_success()
    {
        $mockCourses = [
            [
                'id' => 'course-1',
                'name' => 'Test Course',
                'section' => 'Section A',
                'description' => 'Test Description'
            ]
        ];

        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('getCourses')
            ->once()
            ->andReturn($mockCourses);

        $response = $this->postJson('/classroom/fetch-courses');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'courses' => $mockCourses
        ]);
    }

    public function test_fetch_courses_authentication_failure()
    {
        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(false);

        $response = $this->postJson('/classroom/fetch-courses');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to authenticate with Google Classroom. Please reconnect your account.'
        ]);
    }

    public function test_fetch_courses_api_exception()
    {
        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('getCourses')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $response = $this->postJson('/classroom/fetch-courses');

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to fetch courses: API Error'
        ]);
    }

    public function test_connect_subject_success()
    {
        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'gcr_class_id' => null
        ]);

        $response = $this->postJson('/classroom/connect-subject', [
            'subject_id' => $subject->id,
            'gcr_class_id' => 'gcr-class-1'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Subject successfully connected to Google Classroom course.'
        ]);

        $subject->refresh();
        $this->assertEquals('gcr-class-1', $subject->gcr_class_id);
    }

    public function test_connect_subject_already_connected()
    {
        $existingSubject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'gcr_class_id' => 'gcr-class-1'
        ]);

        $newSubject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'gcr_class_id' => null
        ]);

        $response = $this->postJson('/classroom/connect-subject', [
            'subject_id' => $newSubject->id,
            'gcr_class_id' => 'gcr-class-1'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'This Google Classroom course is already connected to another subject.'
        ]);
    }

    public function test_connect_subject_validation_error()
    {
        $response = $this->postJson('/classroom/connect-subject', [
            'subject_id' => 999,
            'gcr_class_id' => 'gcr-class-1'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject_id']);
    }

    public function test_disconnect_subject_success()
    {
        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'gcr_class_id' => 'gcr-class-1'
        ]);

        // Create student mappings
        StudentMapping::factory()->create([
            'subject_id' => $subject->id,
            'gcr_student_id' => 'student-1'
        ]);

        $response = $this->postJson('/classroom/disconnect-subject', [
            'subject_id' => $subject->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Subject disconnected from Google Classroom.'
        ]);

        $subject->refresh();
        $this->assertNull($subject->gcr_class_id);
        
        // Verify student mappings were deleted
        $this->assertEquals(0, StudentMapping::where('subject_id', $subject->id)->count());
    }

    public function test_sync_students_success()
    {
        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'gcr_class_id' => 'gcr-class-1'
        ]);

        $mockStudents = [
            [
                'user_id' => 'student-1',
                'course_id' => 'gcr-class-1',
                'profile' => [
                    'id' => 'student-1',
                    'name' => 'John Doe',
                    'email_address' => 'john.doe@lorma.edu'
                ]
            ],
            [
                'user_id' => 'student-2',
                'course_id' => 'gcr-class-1',
                'profile' => [
                    'id' => 'student-2',
                    'name' => 'Jane Smith',
                    'email_address' => 'jane.smith@lorma.edu'
                ]
            ]
        ];

        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('getCourseStudents')
            ->once()
            ->with('gcr-class-1')
            ->andReturn($mockStudents);

        $response = $this->postJson('/classroom/sync-students', [
            'subject_id' => $subject->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'synced_count' => 2,
            'errors' => []
        ]);

        // Verify student mappings were created
        $this->assertEquals(2, StudentMapping::where('subject_id', $subject->id)->count());
        
        $mapping1 = StudentMapping::where('gcr_student_id', 'student-1')->first();
        $this->assertEquals('John Doe', $mapping1->student_name);
        $this->assertEquals('john.doe@lorma.edu', $mapping1->student_email);
    }

    public function test_sync_students_no_duplicates()
    {
        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id,
            'gcr_class_id' => 'gcr-class-1'
        ]);

        // Create existing mapping
        StudentMapping::factory()->create([
            'subject_id' => $subject->id,
            'gcr_student_id' => 'student-1'
        ]);

        $mockStudents = [
            [
                'user_id' => 'student-1',
                'course_id' => 'gcr-class-1',
                'profile' => [
                    'id' => 'student-1',
                    'name' => 'John Doe',
                    'email_address' => 'john.doe@lorma.edu'
                ]
            ]
        ];

        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('getCourseStudents')
            ->once()
            ->with('gcr-class-1')
            ->andReturn($mockStudents);

        $response = $this->postJson('/classroom/sync-students', [
            'subject_id' => $subject->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'synced_count' => 0,
            'errors' => []
        ]);

        // Verify no duplicate mappings were created
        $this->assertEquals(1, StudentMapping::where('subject_id', $subject->id)->count());
    }

    public function test_get_course_details_success()
    {
        $mockCourses = [
            [
                'id' => 'gcr-class-1',
                'name' => 'Test Course',
                'section' => 'Section A',
                'description' => 'Test Description',
                'alternate_link' => 'https://classroom.google.com/c/gcr-class-1'
            ]
        ];

        $mockStudents = [
            ['user_id' => 'student-1'],
            ['user_id' => 'student-2']
        ];

        $mockCoursework = [
            ['id' => 'work-1', 'title' => 'Assignment 1']
        ];

        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('getCourses')
            ->once()
            ->andReturn($mockCourses);

        $this->mockClassroomService->shouldReceive('getCourseStudents')
            ->once()
            ->with('gcr-class-1')
            ->andReturn($mockStudents);

        $this->mockClassroomService->shouldReceive('getCourseWork')
            ->once()
            ->with('gcr-class-1')
            ->andReturn($mockCoursework);

        $response = $this->postJson('/classroom/course-details', [
            'gcr_class_id' => 'gcr-class-1'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'course' => $mockCourses[0],
            'students_count' => 2,
            'coursework_count' => 1,
            'students' => $mockStudents,
            'coursework' => $mockCoursework
        ]);
    }

    public function test_get_course_details_course_not_found()
    {
        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('getCourses')
            ->once()
            ->andReturn([]);

        $response = $this->postJson('/classroom/course-details', [
            'gcr_class_id' => 'non-existent-class'
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Course not found.'
        ]);
    }

    public function test_test_connection_success()
    {
        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('testConnection')
            ->once()
            ->andReturn(true);

        $response = $this->postJson('/classroom/test-connection');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Google Classroom connection successful.'
        ]);
    }

    public function test_test_connection_authentication_failure()
    {
        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(false);

        $response = $this->postJson('/classroom/test-connection');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Authentication failed. Please reconnect your Google account.'
        ]);
    }

    public function test_test_connection_failure()
    {
        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with($this->faculty)
            ->andReturn(true);

        $this->mockClassroomService->shouldReceive('testConnection')
            ->once()
            ->andReturn(false);

        $response = $this->postJson('/classroom/test-connection');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Google Classroom connection failed.'
        ]);
    }

    public function test_unauthorized_access_redirects_to_login()
    {
        // Test without authentication
        auth()->logout();

        $response = $this->get('/classroom');

        $response->assertRedirect('/login');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}