<?php

namespace App\Services;

use Google\Client;
use Google\Service\Classroom;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Log;
use App\Models\Faculty;

class GoogleClassroomService
{
    private Client $client;
    private Classroom $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->setScopes(explode(',', config('services.google.classroom_scopes')));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        $this->service = new Classroom($this->client);
    }

    /**
     * Set access token for authenticated requests
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->client->setAccessToken($accessToken);
    }

    /**
     * Set faculty credentials and refresh token if needed
     */
    public function authenticateWithFaculty(Faculty $faculty): bool
    {
        try {
            // Handle different token formats
            $accessToken = $faculty->access_token;
            
            // If it's a JSON string, decode it
            if (is_string($accessToken) && json_decode($accessToken, true)) {
                $tokenArray = json_decode($accessToken, true);
            } else if (is_string($accessToken)) {
                // If it's a plain access token string, create proper token array
                $tokenArray = [
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer'
                ];
                
                // Add refresh token if available
                if ($faculty->refresh_token) {
                    $tokenArray['refresh_token'] = $faculty->refresh_token;
                }
                
                // Add expiry if available
                if ($faculty->token_expires_at) {
                    $tokenArray['expires_in'] = $faculty->token_expires_at->diffInSeconds(now());
                }
            } else {
                $tokenArray = $accessToken;
            }
            
            $this->client->setAccessToken($tokenArray);
            
            // Check if token is expired and refresh if needed
            if ($this->client->isAccessTokenExpired()) {
                if ($faculty->refresh_token) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($faculty->refresh_token);
                    
                    if (isset($newToken['access_token']) && !isset($newToken['error'])) {
                        // Update faculty with new token
                        $faculty->update([
                            'access_token' => json_encode($newToken),
                            'token_expires_at' => isset($newToken['expires_in']) ? 
                                now()->addSeconds($newToken['expires_in']) : 
                                now()->addHour()
                        ]);
                        
                        // Set the new token
                        $this->client->setAccessToken($newToken);
                        return true;
                    }
                }
                
                Log::warning('Google token expired and could not be refreshed', [
                    'faculty_id' => $faculty->id,
                    'has_refresh_token' => !empty($faculty->refresh_token)
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Google Classroom authentication failed', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Also output to console if running in CLI
            if (app()->runningInConsole()) {
                echo "Authentication error: " . $e->getMessage() . PHP_EOL;
                echo "Error code: " . $e->getCode() . PHP_EOL;
            }
            
            return false;
        }
    }

    /**
     * Fetch all courses for the authenticated user
     */
    public function getCourses(): array
    {
        try {
            $courses = [];
            $pageToken = null;
            
            do {
                $params = [
                    'teacherId' => 'me',
                    'courseStates' => ['ACTIVE'],
                    'pageSize' => 100
                ];
                
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                
                $response = $this->service->courses->listCourses($params);
                
                if ($response->getCourses()) {
                    foreach ($response->getCourses() as $course) {
                        $courses[] = [
                            'id' => $course->getId(),
                            'name' => $course->getName(),
                            'section' => $course->getSection(),
                            'description' => $course->getDescription(),
                            'room' => $course->getRoom(),
                            'creation_time' => $course->getCreationTime(),
                            'update_time' => $course->getUpdateTime(),
                            'enrollment_code' => $course->getEnrollmentCode(),
                            'course_state' => $course->getCourseState(),
                            'alternate_link' => $course->getAlternateLink()
                        ];
                    }
                }
                
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);
            
            return $courses;
        } catch (GoogleServiceException $e) {
            Log::error('Failed to fetch Google Classroom courses', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new \Exception('Failed to fetch courses from Google Classroom: ' . $e->getMessage());
        }
    }

    /**
     * Fetch students for a specific course
     */
    public function getCourseStudents(string $courseId): array
    {
        try {
            $students = [];
            $pageToken = null;
            
            do {
                $params = ['pageSize' => 100];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                
                $response = $this->service->courses_students->listCoursesStudents($courseId, $params);
                
                if ($response->getStudents()) {
                    foreach ($response->getStudents() as $student) {
                        $profile = $student->getProfile();
                        $students[] = [
                            'user_id' => $student->getUserId(),
                            'course_id' => $student->getCourseId(),
                            'profile' => [
                                'id' => $profile->getId(),
                                'name' => $profile->getName()->getFullName(),
                                'given_name' => $profile->getName()->getGivenName(),
                                'family_name' => $profile->getName()->getFamilyName(),
                                'email_address' => $profile->getEmailAddress(),
                                'photo_url' => $profile->getPhotoUrl()
                            ]
                        ];
                    }
                }
                
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);
            
            return $students;
        } catch (GoogleServiceException $e) {
            Log::error('Failed to fetch course students', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new \Exception('Failed to fetch students for course: ' . $e->getMessage());
        }
    }

    /**
     * Fetch coursework (assignments) for a specific course
     */
    public function getCourseWork(string $courseId): array
    {
        try {
            $coursework = [];
            $pageToken = null;
            
            do {
                $params = ['pageSize' => 100];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                
                $response = $this->service->courses_courseWork->listCoursesCourseWork($courseId, $params);
                
                if ($response->getCourseWork()) {
                    foreach ($response->getCourseWork() as $work) {
                        $coursework[] = [
                            'id' => $work->getId(),
                            'course_id' => $work->getCourseId(),
                            'title' => $work->getTitle(),
                            'description' => $work->getDescription(),
                            'state' => $work->getState(),
                            'creation_time' => $work->getCreationTime(),
                            'update_time' => $work->getUpdateTime(),
                            'due_date' => $work->getDueDate(),
                            'due_time' => $work->getDueTime(),
                            'max_points' => $work->getMaxPoints(),
                            'work_type' => $work->getWorkType(),
                            'alternate_link' => $work->getAlternateLink()
                        ];
                    }
                }
                
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);
            
            return $coursework;
        } catch (GoogleServiceException $e) {
            Log::error('Failed to fetch coursework', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new \Exception('Failed to fetch coursework for course: ' . $e->getMessage());
        }
    }

    /**
     * Fetch student submissions for a specific coursework
     */
    public function getStudentSubmissions(string $courseId, string $courseWorkId): array
    {
        try {
            $submissions = [];
            $pageToken = null;
            
            do {
                $params = ['pageSize' => 100];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }
                
                $response = $this->service->courses_courseWork_studentSubmissions
                    ->listCoursesCourseWorkStudentSubmissions($courseId, $courseWorkId, $params);
                
                if ($response->getStudentSubmissions()) {
                    foreach ($response->getStudentSubmissions() as $submission) {
                        $submissions[] = [
                            'id' => $submission->getId(),
                            'course_id' => $submission->getCourseId(),
                            'course_work_id' => $submission->getCourseWorkId(),
                            'user_id' => $submission->getUserId(),
                            'creation_time' => $submission->getCreationTime(),
                            'update_time' => $submission->getUpdateTime(),
                            'state' => $submission->getState(),
                            'late' => $submission->getLate(),
                            'draft_grade' => $submission->getDraftGrade(),
                            'assigned_grade' => $submission->getAssignedGrade(),
                            'alternate_link' => $submission->getAlternateLink()
                        ];
                    }
                }
                
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);
            
            return $submissions;
        } catch (GoogleServiceException $e) {
            Log::error('Failed to fetch student submissions', [
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new \Exception('Failed to fetch student submissions: ' . $e->getMessage());
        }
    }

    /**
     * Fetch students for a specific course (alias for getCourseStudents)
     */
    public function getStudents(string $courseId): array
    {
        return $this->getCourseStudents($courseId);
    }

    /**
     * Test the connection to Google Classroom API
     */
    public function testConnection(): bool
    {
        try {
            $this->service->courses->listCourses(['pageSize' => 1]);
            return true;
        } catch (\Exception $e) {
            Log::error('Google Classroom connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}