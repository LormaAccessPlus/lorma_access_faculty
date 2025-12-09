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
     * @param bool $includeArchived Whether to include archived courses
     */
    public function getCourses(bool $includeArchived = false): array
    {
        try {
            $courses = [];
            $pageToken = null;
            
            // Define which course states to fetch
            $courseStates = $includeArchived ? ['ACTIVE', 'ARCHIVED'] : ['ACTIVE'];
            
            do {
                $params = [
                    'teacherId' => 'me',
                    'courseStates' => $courseStates,
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
     * Get a single course by ID to check its current state
     */
    public function getCourse(string $courseId): ?array
    {
        try {
            $course = $this->service->courses->get($courseId);
            
            return [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'section' => $course->getSection(),
                'course_state' => $course->getCourseState(),
                'alternate_link' => $course->getAlternateLink()
            ];
        } catch (GoogleServiceException $e) {
            Log::error('Failed to fetch Google Classroom course', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return null;
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
                        
                        // Get name information safely
                        $name = $profile->getName();
                        $fullName = $name ? $name->getFullName() : null;
                        $givenName = $name ? $name->getGivenName() : null;
                        $familyName = $name ? $name->getFamilyName() : null;
                        
                        // Get email safely
                        $emailAddress = $profile->getEmailAddress();
                        
                        // Log if email is missing for debugging
                        if (!$emailAddress) {
                            Log::warning('Student without email address', [
                                'user_id' => $student->getUserId(),
                                'name' => $fullName,
                                'course_id' => $courseId
                            ]);
                        }
                        
                        $students[] = [
                            'userId' => $student->getUserId(),
                            'courseId' => $student->getCourseId(),
                            'emailAddress' => $emailAddress,
                            'profile' => [
                                'id' => $profile->getId(),
                                'name' => [
                                    'fullName' => $fullName ?? 'Unknown',
                                    'givenName' => $givenName,
                                    'familyName' => $familyName,
                                ],
                                'emailAddress' => $emailAddress,
                                'photoUrl' => $profile->getPhotoUrl()
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
     * Fetch quizzes (coursework with quiz-like names) for a specific course
     * This filters coursework to identify quizzes based on naming patterns
     */
    public function getQuizzes(string $courseId): array
    {
        try {
            $allCoursework = $this->getCourseWork($courseId);
            $quizzes = [];
            
            // Filter coursework that looks like quizzes
            foreach ($allCoursework as $work) {
                $title = strtolower($work['title']);
                
                // Check if the title contains quiz-related keywords
                if (str_contains($title, 'quiz') || 
                    str_contains($title, 'test') || 
                    str_contains($title, 'exam') ||
                    preg_match('/\bq\d+\b/', $title)) { // Matches Q1, Q2, etc.
                    
                    $quizzes[] = [
                        'id' => $work['id'],
                        'course_id' => $work['course_id'],
                        'title' => $work['title'],
                        'description' => $work['description'],
                        'max_points' => $work['max_points'],
                        'due_date' => $work['due_date'],
                        'due_time' => $work['due_time'],
                        'creation_time' => $work['creation_time'],
                        'state' => $work['state'],
                        'alternate_link' => $work['alternate_link']
                    ];
                }
            }
            
            return $quizzes;
        } catch (\Exception $e) {
            Log::error('Failed to fetch quizzes', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch quizzes for course: ' . $e->getMessage());
        }
    }

    /**
     * Fetch activities (coursework excluding quizzes) for a specific course
     * This filters coursework to identify regular activities
     */
    public function getActivities(string $courseId): array
    {
        try {
            $allCoursework = $this->getCourseWork($courseId);
            $activities = [];
            
            // Filter coursework that doesn't look like quizzes
            foreach ($allCoursework as $work) {
                $title = strtolower($work['title']);
                
                // Exclude quiz-related keywords
                if (!str_contains($title, 'quiz') && 
                    !str_contains($title, 'test') && 
                    !str_contains($title, 'exam') &&
                    !preg_match('/\bq\d+\b/', $title)) {
                    
                    $activities[] = [
                        'id' => $work['id'],
                        'course_id' => $work['course_id'],
                        'title' => $work['title'],
                        'description' => $work['description'],
                        'max_points' => $work['max_points'],
                        'due_date' => $work['due_date'],
                        'due_time' => $work['due_time'],
                        'creation_time' => $work['creation_time'],
                        'state' => $work['state'],
                        'alternate_link' => $work['alternate_link']
                    ];
                }
            }
            
            return $activities;
        } catch (\Exception $e) {
            Log::error('Failed to fetch activities', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch activities for course: ' . $e->getMessage());
        }
    }

    /**
     * Fetch quiz submissions for a specific quiz
     * This is an alias for getStudentSubmissions but specifically for quizzes
     */
    public function getQuizSubmissions(string $courseId, string $quizId): array
    {
        return $this->getStudentSubmissions($courseId, $quizId);
    }

    /**
     * Fetch coursework with categorization (activities vs quizzes)
     * Returns an array with 'activities' and 'quizzes' keys
     */
    public function getCategorizedCourseWork(string $courseId): array
    {
        try {
            $allCoursework = $this->getCourseWork($courseId);
            $categorized = [
                'activities' => [],
                'quizzes' => []
            ];
            
            foreach ($allCoursework as $work) {
                $title = strtolower($work['title']);
                
                // Categorize based on title
                if (str_contains($title, 'quiz') || 
                    str_contains($title, 'test') || 
                    str_contains($title, 'exam') ||
                    preg_match('/\bq\d+\b/', $title)) {
                    $categorized['quizzes'][] = $work;
                } else {
                    $categorized['activities'][] = $work;
                }
            }
            
            return $categorized;
        } catch (\Exception $e) {
            Log::error('Failed to fetch categorized coursework', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch categorized coursework: ' . $e->getMessage());
        }
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

    /**
     * Check if a subject's GCR classroom is archived and archive it if needed
     */
    public function checkAndArchiveSubject(\App\Models\Subject $subject): bool
    {
        if (!$subject->gcr_class_id || $subject->archived_at) {
            return false;
        }

        try {
            $course = $this->getCourse($subject->gcr_class_id);
            
            if ($course && isset($course['course_state']) && $course['course_state'] === 'ARCHIVED') {
                $subject->update([
                    'archived_at' => now(),
                    'gcr_course_state' => 'ARCHIVED'
                ]);
                
                Log::info('Subject auto-archived', [
                    'subject_id' => $subject->id,
                    'subject_code' => $subject->subject_code,
                    'gcr_class_id' => $subject->gcr_class_id
                ]);
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error checking subject archive status', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}