<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GoogleClassroomService;
use App\Models\Faculty;
use Google\Client;
use Google\Service\Classroom;
use Google\Service\Exception as GoogleServiceException;
use Mockery;

class GoogleClassroomServiceTest extends TestCase
{
    private GoogleClassroomService $service;
    private $mockClient;
    private $mockClassroomService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Google Client and Classroom Service
        $this->mockClient = Mockery::mock(Client::class);
        $this->mockClassroomService = Mockery::mock(Classroom::class);
        
        // Create service instance and inject mocks
        $this->service = new GoogleClassroomService();
        
        // Use reflection to inject mocks
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $this->mockClient);
        
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($this->service, $this->mockClassroomService);
    }

    public function test_set_access_token()
    {
        $token = 'test-access-token';
        
        $this->mockClient->shouldReceive('setAccessToken')
            ->once()
            ->with($token);
        
        $this->service->setAccessToken($token);
        
        $this->assertTrue(true); // Add assertion to avoid risky test
    }

    public function test_authenticate_with_faculty_success()
    {
        $faculty = new Faculty([
            'id' => 1,
            'access_token' => json_encode(['access_token' => 'valid-token']),
            'refresh_token' => 'refresh-token'
        ]);

        $this->mockClient->shouldReceive('setAccessToken')
            ->once()
            ->with($faculty->access_token);
        
        $this->mockClient->shouldReceive('isAccessTokenExpired')
            ->once()
            ->andReturn(false);

        $result = $this->service->authenticateWithFaculty($faculty);
        
        $this->assertTrue($result);
    }

    public function test_authenticate_with_faculty_token_refresh()
    {
        $faculty = Mockery::mock(Faculty::class);
        $faculty->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $faculty->shouldReceive('getAttribute')->with('access_token')->andReturn(json_encode(['access_token' => 'expired-token']));
        $faculty->shouldReceive('getAttribute')->with('refresh_token')->andReturn('refresh-token');
        
        $newToken = ['access_token' => 'new-token', 'expires_in' => 3600];

        $this->mockClient->shouldReceive('setAccessToken')
            ->once()
            ->with($faculty->access_token);
        
        $this->mockClient->shouldReceive('isAccessTokenExpired')
            ->once()
            ->andReturn(true);
        
        $this->mockClient->shouldReceive('setRefreshToken')
            ->once()
            ->with($faculty->refresh_token);
        
        $this->mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->once()
            ->andReturn($newToken);

        $faculty->shouldReceive('update')
            ->once()
            ->with(['access_token' => json_encode($newToken)]);

        $result = $this->service->authenticateWithFaculty($faculty);
        
        $this->assertTrue($result);
    }

    public function test_authenticate_with_faculty_failure()
    {
        $faculty = new Faculty([
            'id' => 1,
            'access_token' => json_encode(['access_token' => 'expired-token']),
            'refresh_token' => null
        ]);

        $this->mockClient->shouldReceive('setAccessToken')
            ->once()
            ->with($faculty->access_token);
        
        $this->mockClient->shouldReceive('isAccessTokenExpired')
            ->once()
            ->andReturn(true);

        $result = $this->service->authenticateWithFaculty($faculty);
        
        $this->assertFalse($result);
    }

    public function test_get_courses_success()
    {
        $mockCourses = Mockery::mock();
        $mockCourse = Mockery::mock();
        
        $mockCourse->shouldReceive('getId')->andReturn('course-1');
        $mockCourse->shouldReceive('getName')->andReturn('Test Course');
        $mockCourse->shouldReceive('getSection')->andReturn('Section A');
        $mockCourse->shouldReceive('getDescription')->andReturn('Test Description');
        $mockCourse->shouldReceive('getRoom')->andReturn('Room 101');
        $mockCourse->shouldReceive('getCreationTime')->andReturn('2024-01-01T00:00:00Z');
        $mockCourse->shouldReceive('getUpdateTime')->andReturn('2024-01-02T00:00:00Z');
        $mockCourse->shouldReceive('getEnrollmentCode')->andReturn('ABC123');
        $mockCourse->shouldReceive('getCourseState')->andReturn('ACTIVE');
        $mockCourse->shouldReceive('getAlternateLink')->andReturn('https://classroom.google.com/c/course-1');

        $mockResponse = Mockery::mock();
        $mockResponse->shouldReceive('getCourses')->andReturn([$mockCourse]);
        $mockResponse->shouldReceive('getNextPageToken')->andReturn(null);

        $mockCoursesResource = Mockery::mock();
        $mockCoursesResource->shouldReceive('listCourses')
            ->once()
            ->with([
                'teacherId' => 'me',
                'courseStates' => ['ACTIVE'],
                'pageSize' => 100
            ])
            ->andReturn($mockResponse);

        $this->mockClassroomService->courses = $mockCoursesResource;

        $courses = $this->service->getCourses();
        
        $this->assertCount(1, $courses);
        $this->assertEquals('course-1', $courses[0]['id']);
        $this->assertEquals('Test Course', $courses[0]['name']);
        $this->assertEquals('Section A', $courses[0]['section']);
    }

    public function test_get_courses_api_exception()
    {
        $mockCoursesResource = Mockery::mock();
        $mockCoursesResource->shouldReceive('listCourses')
            ->once()
            ->andThrow(new GoogleServiceException('API Error', 403));

        $this->mockClassroomService->courses = $mockCoursesResource;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch courses from Google Classroom: API Error');

        $this->service->getCourses();
    }

    public function test_get_course_students_success()
    {
        $courseId = 'course-1';
        
        $mockProfile = Mockery::mock();
        $mockProfile->shouldReceive('getId')->andReturn('student-1');
        $mockProfile->shouldReceive('getName')->andReturnSelf();
        $mockProfile->shouldReceive('getFullName')->andReturn('John Doe');
        $mockProfile->shouldReceive('getGivenName')->andReturn('John');
        $mockProfile->shouldReceive('getFamilyName')->andReturn('Doe');
        $mockProfile->shouldReceive('getEmailAddress')->andReturn('john.doe@lorma.edu');
        $mockProfile->shouldReceive('getPhotoUrl')->andReturn('https://photo.url');

        $mockStudent = Mockery::mock();
        $mockStudent->shouldReceive('getUserId')->andReturn('student-1');
        $mockStudent->shouldReceive('getCourseId')->andReturn($courseId);
        $mockStudent->shouldReceive('getProfile')->andReturn($mockProfile);

        $mockResponse = Mockery::mock();
        $mockResponse->shouldReceive('getStudents')->andReturn([$mockStudent]);
        $mockResponse->shouldReceive('getNextPageToken')->andReturn(null);

        $mockStudentsResource = Mockery::mock();
        $mockStudentsResource->shouldReceive('listCoursesStudents')
            ->once()
            ->with($courseId, ['pageSize' => 100])
            ->andReturn($mockResponse);

        $this->mockClassroomService->courses_students = $mockStudentsResource;

        $students = $this->service->getCourseStudents($courseId);
        
        $this->assertCount(1, $students);
        $this->assertEquals('student-1', $students[0]['user_id']);
        $this->assertEquals($courseId, $students[0]['course_id']);
        $this->assertEquals('John Doe', $students[0]['profile']['name']);
        $this->assertEquals('john.doe@lorma.edu', $students[0]['profile']['email_address']);
    }

    public function test_get_course_work_success()
    {
        $courseId = 'course-1';
        
        $mockWork = Mockery::mock();
        $mockWork->shouldReceive('getId')->andReturn('work-1');
        $mockWork->shouldReceive('getCourseId')->andReturn($courseId);
        $mockWork->shouldReceive('getTitle')->andReturn('Assignment 1');
        $mockWork->shouldReceive('getDescription')->andReturn('Test assignment');
        $mockWork->shouldReceive('getState')->andReturn('PUBLISHED');
        $mockWork->shouldReceive('getCreationTime')->andReturn('2024-01-01T00:00:00Z');
        $mockWork->shouldReceive('getUpdateTime')->andReturn('2024-01-02T00:00:00Z');
        $mockWork->shouldReceive('getDueDate')->andReturn(null);
        $mockWork->shouldReceive('getDueTime')->andReturn(null);
        $mockWork->shouldReceive('getMaxPoints')->andReturn(100);
        $mockWork->shouldReceive('getWorkType')->andReturn('ASSIGNMENT');
        $mockWork->shouldReceive('getAlternateLink')->andReturn('https://classroom.google.com/c/course-1/a/work-1');

        $mockResponse = Mockery::mock();
        $mockResponse->shouldReceive('getCourseWork')->andReturn([$mockWork]);
        $mockResponse->shouldReceive('getNextPageToken')->andReturn(null);

        $mockCourseWorkResource = Mockery::mock();
        $mockCourseWorkResource->shouldReceive('listCoursesCourseWork')
            ->once()
            ->with($courseId, ['pageSize' => 100])
            ->andReturn($mockResponse);

        $this->mockClassroomService->courses_courseWork = $mockCourseWorkResource;

        $coursework = $this->service->getCourseWork($courseId);
        
        $this->assertCount(1, $coursework);
        $this->assertEquals('work-1', $coursework[0]['id']);
        $this->assertEquals($courseId, $coursework[0]['course_id']);
        $this->assertEquals('Assignment 1', $coursework[0]['title']);
        $this->assertEquals(100, $coursework[0]['max_points']);
    }

    public function test_get_student_submissions_success()
    {
        $courseId = 'course-1';
        $courseWorkId = 'work-1';
        
        $mockSubmission = Mockery::mock();
        $mockSubmission->shouldReceive('getId')->andReturn('submission-1');
        $mockSubmission->shouldReceive('getCourseId')->andReturn($courseId);
        $mockSubmission->shouldReceive('getCourseWorkId')->andReturn($courseWorkId);
        $mockSubmission->shouldReceive('getUserId')->andReturn('student-1');
        $mockSubmission->shouldReceive('getCreationTime')->andReturn('2024-01-01T00:00:00Z');
        $mockSubmission->shouldReceive('getUpdateTime')->andReturn('2024-01-02T00:00:00Z');
        $mockSubmission->shouldReceive('getState')->andReturn('TURNED_IN');
        $mockSubmission->shouldReceive('getLate')->andReturn(false);
        $mockSubmission->shouldReceive('getDraftGrade')->andReturn(85);
        $mockSubmission->shouldReceive('getAssignedGrade')->andReturn(85);
        $mockSubmission->shouldReceive('getAlternateLink')->andReturn('https://classroom.google.com/c/course-1/a/work-1/submissions/by-status/and-sort-first-name/all');

        $mockResponse = Mockery::mock();
        $mockResponse->shouldReceive('getStudentSubmissions')->andReturn([$mockSubmission]);
        $mockResponse->shouldReceive('getNextPageToken')->andReturn(null);

        $mockSubmissionsResource = Mockery::mock();
        $mockSubmissionsResource->shouldReceive('listCoursesCourseWorkStudentSubmissions')
            ->once()
            ->with($courseId, $courseWorkId, ['pageSize' => 100])
            ->andReturn($mockResponse);

        $this->mockClassroomService->courses_courseWork_studentSubmissions = $mockSubmissionsResource;

        $submissions = $this->service->getStudentSubmissions($courseId, $courseWorkId);
        
        $this->assertCount(1, $submissions);
        $this->assertEquals('submission-1', $submissions[0]['id']);
        $this->assertEquals($courseId, $submissions[0]['course_id']);
        $this->assertEquals($courseWorkId, $submissions[0]['course_work_id']);
        $this->assertEquals('student-1', $submissions[0]['user_id']);
        $this->assertEquals(85, $submissions[0]['assigned_grade']);
    }

    public function test_test_connection_success()
    {
        $mockCoursesResource = Mockery::mock();
        $mockCoursesResource->shouldReceive('listCourses')
            ->once()
            ->with(['pageSize' => 1])
            ->andReturn(Mockery::mock());

        $this->mockClassroomService->courses = $mockCoursesResource;

        $result = $this->service->testConnection();
        
        $this->assertTrue($result);
    }

    public function test_test_connection_failure()
    {
        $mockCoursesResource = Mockery::mock();
        $mockCoursesResource->shouldReceive('listCourses')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $this->mockClassroomService->courses = $mockCoursesResource;

        $result = $this->service->testConnection();
        
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}