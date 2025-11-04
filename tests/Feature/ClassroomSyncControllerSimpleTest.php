<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Faculty;
use App\Services\GoogleClassroomService;
use Mockery;

class ClassroomSyncControllerSimpleTest extends TestCase
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
        
        // Mock authentication
        $this->be($this->faculty, 'faculty');
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
            ->with(Mockery::type(Faculty::class))
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
            ->with(Mockery::type(Faculty::class))
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
            ->with(Mockery::type(Faculty::class))
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

    public function test_test_connection_success()
    {
        $this->mockClassroomService->shouldReceive('authenticateWithFaculty')
            ->once()
            ->with(Mockery::type(Faculty::class))
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
            ->with(Mockery::type(Faculty::class))
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
            ->with(Mockery::type(Faculty::class))
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