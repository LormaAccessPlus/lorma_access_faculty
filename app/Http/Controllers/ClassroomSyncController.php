<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Services\GoogleClassroomService;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\StudentMapping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClassroomSyncController extends Controller
{
    private GoogleClassroomService $classroomService;

    public function __construct(GoogleClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }

    /**
     * Display the Google Classroom integration dashboard
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get current academic year and semester
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Only get subjects from the current semester
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->active()
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->get();
        
        // Get subjects that are already connected to GCR
        $connectedSubjects = $subjects->whereNotNull('gcr_class_id');
        
        // Get subjects that need GCR connection
        $unconnectedSubjects = $subjects->whereNull('gcr_class_id');
        
        return view('classroom.index', compact('connectedSubjects', 'unconnectedSubjects'));
    }

    /**
     * Fetch Google Classroom courses for the authenticated faculty
     */
    public function fetchCourses(Request $request): JsonResponse
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom. Please reconnect your account.'
                ], 401);
            }
            
            $courses = $this->classroomService->getCourses();
            
            // Get all GCR class IDs that are already mapped to subjects
            $mappedGcrClassIds = Subject::where('faculty_id', $faculty->id)
                ->active()
                ->whereNotNull('gcr_class_id')
                ->pluck('gcr_class_id')
                ->toArray();
            
            // Filter out courses that are already mapped
            $availableCourses = array_filter($courses, function($course) use ($mappedGcrClassIds) {
                return !in_array($course['id'], $mappedGcrClassIds);
            });
            
            // Re-index the array to ensure proper JSON encoding
            $availableCourses = array_values($availableCourses);
            
            return response()->json([
                'success' => true,
                'courses' => $availableCourses
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Classroom courses', [
                'faculty_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch courses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connect a subject to a Google Classroom course
     */
    public function connectSubject(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'gcr_class_id' => 'required|string'
        ]);

        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            $subject = Subject::where('id', $request->subject_id)
                ->where('faculty_id', $faculty->id)
                ->firstOrFail();

            // Check if this GCR class is already connected to another subject
            $existingConnection = Subject::where('gcr_class_id', $request->gcr_class_id)
                ->where('faculty_id', $faculty->id)
                ->where('id', '!=', $subject->id)
                ->first();

            if ($existingConnection) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Google Classroom course is already connected to another subject.'
                ], 422);
            }

            // Authenticate and fetch course details to get the course state
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            $courses = $this->classroomService->getCourses();
            $course = collect($courses)->firstWhere('id', $request->gcr_class_id);

            // Update subject with GCR class ID, course name, and course state
            $subject->update([
                'gcr_class_id' => $request->gcr_class_id,
                'gcr_class_name' => $course['name'] ?? null,
                'gcr_course_state' => $course['course_state'] ?? 'ACTIVE'
            ]);

            // Automatically fetch and sync students from Google Classroom
            try {
                $students = $this->classroomService->getStudents($request->gcr_class_id);
                $syncedCount = 0;
                $matchedCount = 0;

                // Get existing students for this subject
                $existingMappings = StudentMapping::where('subject_id', $subject->id)->get();

                foreach ($students as $student) {
                    $gcrName = $student['profile']['name']['fullName'] ?? '';
                    $gcrEmail = $student['profile']['emailAddress'] ?? '';
                    $gcrUserId = $student['userId'];

                    // Check if this GCR student already exists
                    $existingByGcrId = $existingMappings->firstWhere('gcr_student_id', $gcrUserId);
                    if ($existingByGcrId) {
                        // Already exists, skip
                        continue;
                    }

                    // Try to match with existing CSV students (those without GCR ID)
                    $matched = false;
                    $bestMatch = null;
                    $bestConfidence = 0;

                    foreach ($existingMappings as $existing) {
                        // Skip if already has a GCR ID
                        if ($existing->gcr_student_id) {
                            continue;
                        }

                        // Check email match (highest priority)
                        if ($existing->student_email && $gcrEmail && 
                            strtolower($existing->student_email) === strtolower($gcrEmail)) {
                            $bestMatch = $existing;
                            $bestConfidence = 100;
                            break; // Perfect match, stop searching
                        }
                        
                        // Check name similarity
                        $similarity = $this->calculateNameSimilarity($existing->student_name, $gcrName);
                        if ($similarity >= 80 && $similarity > $bestConfidence) {
                            $bestMatch = $existing;
                            $bestConfidence = $similarity;
                        }
                    }

                    // If we found a match, update it
                    if ($bestMatch && $bestConfidence >= 80) {
                        $bestMatch->update([
                            'gcr_student_id' => $gcrUserId,
                            'student_email' => $gcrEmail, // Update email from GCR if not present
                            'auto_matched' => true,
                            'mapping_confidence' => $bestConfidence,
                        ]);
                        $matched = true;
                        $matchedCount++;
                    } else {
                        // No match found, create new mapping for GCR student
                        StudentMapping::create([
                            'subject_id' => $subject->id,
                            'student_name' => $gcrName,
                            'student_email' => $gcrEmail,
                            'gcr_student_id' => $gcrUserId,
                            'auto_matched' => true,
                            'mapping_confidence' => 100,
                        ]);
                    }
                    $syncedCount++;
                }

                Log::info("Auto-synced {$syncedCount} GCR students, matched {$matchedCount} with existing CSV students for subject {$subject->id}");
            } catch (\Exception $e) {
                Log::warning('Failed to auto-sync students after connecting subject', [
                    'subject_id' => $subject->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the connection if student sync fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Subject successfully connected to Google Classroom course and students synced.',
                'course_state' => $course['course_state'] ?? 'ACTIVE'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to connect subject to GCR course', [
                'subject_id' => $request->subject_id,
                'gcr_class_id' => $request->gcr_class_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect subject: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect a subject from Google Classroom
     */
    public function disconnectSubject(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id'
        ]);

        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            $subject = Subject::where('id', $request->subject_id)
                ->where('faculty_id', $faculty->id)
                ->firstOrFail();

            // Remove GCR class ID but preserve student mappings and grades
            DB::transaction(function () use ($subject) {
                // Only clear GCR connection - preserve all student data
                $subject->update(['gcr_class_id' => null]);
                
                // Note: Student mappings and grades are preserved for historical records
            });

            return response()->json([
                'success' => true,
                'message' => 'Subject disconnected from Google Classroom.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to disconnect subject from GCR', [
                'subject_id' => $request->subject_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect subject: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync students from Google Classroom for a specific subject
     */
    public function syncStudents(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id'
        ]);

        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            $subject = Subject::where('id', $request->subject_id)
                ->where('faculty_id', $faculty->id)
                ->whereNotNull('gcr_class_id')
                ->firstOrFail();

            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            // Fetch students from Google Classroom
            $gcrStudents = $this->classroomService->getCourseStudents($subject->gcr_class_id);

            $syncedCount = 0;
            $errors = [];

            DB::transaction(function () use ($subject, $gcrStudents, &$syncedCount, &$errors) {
                foreach ($gcrStudents as $gcrStudent) {
                    try {
                        // Debug log the student data structure
                        Log::info('Processing GCR student', ['data' => $gcrStudent]);
                        
                        // Get student data with correct keys
                        $userId = $gcrStudent['userId'] ?? $gcrStudent['user_id'] ?? null;
                        
                        // Handle name - could be array or string
                        $studentName = 'Unknown';
                        if (isset($gcrStudent['profile']['name'])) {
                            $nameData = $gcrStudent['profile']['name'];
                            if (is_array($nameData)) {
                                $studentName = $nameData['fullName'] ?? $nameData['full_name'] ?? 'Unknown';
                            } else {
                                $studentName = $nameData;
                            }
                        }
                        
                        // Handle email
                        $email = $gcrStudent['emailAddress'] ?? $gcrStudent['profile']['emailAddress'] ?? $gcrStudent['profile']['email_address'] ?? null;
                        
                        if (!$userId) {
                            $errors[] = "Student missing user ID: " . $studentName;
                            continue;
                        }
                        
                        // Use updateOrCreate to handle potential duplicates
                        $mapping = StudentMapping::updateOrCreate(
                            [
                                'subject_id' => $subject->id,
                                'student_name' => $studentName,
                            ],
                            [
                                'gcr_student_id' => $userId,
                                'student_email' => $email,
                                'mapping_confidence' => 0.0, // Will be updated by matching algorithm
                                'auto_matched' => true,
                            ]
                        );
                        
                        if ($mapping->wasRecentlyCreated) {
                            $syncedCount++;
                            Log::info('Student synced successfully', ['name' => $studentName, 'user_id' => $userId]);
                        } else {
                            Log::info('Student updated successfully', ['name' => $studentName, 'user_id' => $userId]);
                        }
                    } catch (\Exception $e) {
                        $studentName = 'Unknown';
                        if (isset($gcrStudent['profile']['name'])) {
                            $nameData = $gcrStudent['profile']['name'];
                            $studentName = is_array($nameData) ? ($nameData['fullName'] ?? 'Unknown') : $nameData;
                        }
                        Log::error('Failed to sync individual student', ['name' => $studentName, 'error' => $e->getMessage()]);
                        $errors[] = "Failed to sync student {$studentName}: " . $e->getMessage();
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$syncedCount} students from Google Classroom.",
                'synced_count' => $syncedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync students from GCR', [
                'subject_id' => $request->subject_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get course details from Google Classroom
     */
    public function getCourseDetails(Request $request): JsonResponse
    {
        $request->validate([
            'gcr_class_id' => 'required|string'
        ]);

        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            // Fetch course details
            $courses = $this->classroomService->getCourses();
            $course = collect($courses)->firstWhere('id', $request->gcr_class_id);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found.'
                ], 404);
            }

            // Fetch students and coursework
            $students = $this->classroomService->getCourseStudents($request->gcr_class_id);
            $coursework = $this->classroomService->getCourseWork($request->gcr_class_id);

            return response()->json([
                'success' => true,
                'course' => $course,
                'students_count' => count($students),
                'coursework_count' => count($coursework),
                'students' => $students,
                'coursework' => $coursework
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get course details', [
                'gcr_class_id' => $request->gcr_class_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get course details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Google Classroom connection
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please reconnect your Google account.'
                ]);
            }

            $connectionTest = $this->classroomService->testConnection();

            return response()->json([
                'success' => $connectionTest,
                'message' => $connectionTest 
                    ? 'Google Classroom connection successful.' 
                    : 'Google Classroom connection failed.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sync activities from Google Classroom for a specific subject
     */
    public function syncActivities(Request $request, Subject $subject): JsonResponse
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            // Verify subject belongs to authenticated faculty
            if ($subject->faculty_id !== $faculty->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to subject.'
                ], 403);
            }

            if (!$subject->gcr_class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject is not connected to Google Classroom.'
                ], 422);
            }

            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            // Fetch coursework from Google Classroom
            $coursework = $this->classroomService->getCourseWork($subject->gcr_class_id);

            $syncedCount = 0;
            $errors = [];

            DB::transaction(function () use ($subject, $coursework, &$syncedCount, &$errors) {
                foreach ($coursework as $assignment) {
                    try {
                        // Check if activity already exists
                        $existingActivity = $subject->activities()
                            ->where('gcr_assignment_id', $assignment['id'])
                            ->first();

                        if (!$existingActivity) {
                            // Create new activity with default values
                            // Faculty can edit these later through the interface
                            $subject->activities()->create([
                                'name' => $assignment['title'],
                                'type' => 'lecture', // Default to lecture, can be changed
                                'term' => 'prelim', // Default to prelim, can be changed
                                'max_score' => $assignment['max_points'] ?? 100,
                                'weight' => null, // No default weight
                                'gcr_assignment_id' => $assignment['id']
                            ]);
                            $syncedCount++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Failed to sync assignment '{$assignment['title']}': " . $e->getMessage();
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$syncedCount} activities from Google Classroom.",
                'synced_count' => $syncedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync activities from GCR', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync activities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available coursework from Google Classroom for selective import
     */
    public function getAvailableCoursework(Request $request, Subject $subject): JsonResponse
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            // Verify subject belongs to authenticated faculty
            if ($subject->faculty_id !== $faculty->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to subject.'
                ], 403);
            }

            if (!$subject->gcr_class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject is not connected to Google Classroom.'
                ], 422);
            }

            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            // Fetch coursework from Google Classroom
            $coursework = $this->classroomService->getCourseWork($subject->gcr_class_id);

            // Get existing activities to mark which ones are already imported
            $existingActivities = $subject->activities()
                ->whereNotNull('gcr_assignment_id')
                ->pluck('gcr_assignment_id')
                ->toArray();

            // Process coursework data
            $availableCoursework = [];
            foreach ($coursework as $assignment) {
                $isImported = in_array($assignment['id'], $existingActivities);
                
                $availableCoursework[] = [
                    'id' => $assignment['id'],
                    'title' => $assignment['title'],
                    'description' => $assignment['description'] ?? '',
                    'max_points' => $assignment['max_points'] ?? 100,
                    'creation_time' => $assignment['creation_time'],
                    'due_date' => $assignment['due_date'],
                    'state' => $assignment['state'],
                    'alternate_link' => $assignment['alternate_link'],
                    'is_imported' => $isImported
                ];
            }

            return response()->json([
                'success' => true,
                'coursework' => $availableCoursework,
                'total_count' => count($availableCoursework),
                'imported_count' => count(array_filter($availableCoursework, fn($item) => $item['is_imported'])),
                'available_count' => count(array_filter($availableCoursework, fn($item) => !$item['is_imported']))
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch available coursework from GCR', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch coursework: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import selected coursework as activities
     */
    public function importSelectedCoursework(Request $request, Subject $subject): JsonResponse
    {
        try {
            $request->validate([
                'coursework_ids' => 'required|array',
                'coursework_ids.*' => 'required|string',
                'default_term' => 'required|in:prelim,midterm,finals',
                'default_type' => 'required|in:lecture,lab'
            ]);

            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            // Verify subject belongs to authenticated faculty
            if ($subject->faculty_id !== $faculty->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to subject.'
                ], 403);
            }

            if (!$subject->gcr_class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject is not connected to Google Classroom.'
                ], 422);
            }

            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            // Fetch coursework from Google Classroom
            $coursework = $this->classroomService->getCourseWork($subject->gcr_class_id);
            $courseworkById = collect($coursework)->keyBy('id');

            $importedCount = 0;
            $errors = [];

            $gradesImportedCount = 0;
            
            DB::transaction(function () use ($subject, $request, $courseworkById, &$importedCount, &$errors, $faculty, &$gradesImportedCount) {
                // Get student mappings for this subject
                $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();
                
                foreach ($request->coursework_ids as $courseworkId) {
                    try {
                        $assignment = $courseworkById->get($courseworkId);
                        
                        if (!$assignment) {
                            $errors[] = "Coursework with ID {$courseworkId} not found";
                            continue;
                        }

                        // Check if activity already exists
                        $existingActivity = $subject->activities()
                            ->where('gcr_assignment_id', $assignment['id'])
                            ->first();

                        $activity = null;
                        if (!$existingActivity) {
                            // Automatically categorize as quiz or activity based on title
                            $title = strtolower($assignment['title']);
                            $activityCategory = 'activity';
                            
                            if (str_contains($title, 'quiz') || 
                                str_contains($title, 'test') || 
                                str_contains($title, 'exam') ||
                                preg_match('/\bq\d+\b/', $title)) {
                                $activityCategory = 'quiz';
                            }
                            
                            // Create new activity
                            $activity = $subject->activities()->create([
                                'name' => $assignment['title'],
                                'type' => $request->default_type,
                                'term' => $request->default_term,
                                'max_score' => $assignment['max_points'] ?? 100,
                                'weight' => null,
                                'activity_category' => $activityCategory,
                                'gcr_assignment_id' => $assignment['id']
                            ]);
                            
                            // Sync to grading sheet
                            $this->syncActivityToGradingItem($activity);
                            
                            $importedCount++;
                        } else {
                            $activity = $existingActivity;
                            $errors[] = "Activity '{$assignment['title']}' already exists";
                        }

                        // Import grades for this activity
                        if ($activity && $studentMappings->isNotEmpty()) {
                            try {
                                $submissions = $this->classroomService->getStudentSubmissions(
                                    $subject->gcr_class_id,
                                    $assignment['id']
                                );

                                $studentsWithGrades = [];
                                foreach ($submissions as $submission) {
                                    $studentMapping = $studentMappings->firstWhere('gcr_student_id', $submission['user_id']);
                                    
                                    if (!$studentMapping) {
                                        continue;
                                    }

                                    $score = $submission['assigned_grade'] ?? $submission['draft_grade'] ?? null;
                                    
                                    if ($score !== null) {
                                        \App\Models\GradeRecord::updateOrCreate(
                                            [
                                                'student_mapping_id' => $studentMapping->id,
                                                'activity_id' => $activity->id,
                                            ],
                                            [
                                                'score' => $score,
                                                'max_score' => $activity->max_score,
                                                'term' => $activity->term,
                                                'created_by' => $faculty->id,
                                            ]
                                        );
                                        $gradesImportedCount++;
                                        
                                        // Track students who got grades for class standing recalculation
                                        if (!in_array($studentMapping->id, $studentsWithGrades)) {
                                            $studentsWithGrades[] = $studentMapping->id;
                                        }
                                    }
                                }
                                
                                // Recalculate class standing for all students who got grades
                                foreach ($studentsWithGrades as $studentMappingId) {
                                    $studentMapping = $studentMappings->firstWhere('id', $studentMappingId);
                                    if ($studentMapping) {
                                        $this->recalculateClassStanding($studentMapping, $activity->term);
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning('Failed to import grades for activity', [
                                    'activity_id' => $activity->id,
                                    'activity_name' => $activity->name,
                                    'error' => $e->getMessage()
                                ]);
                                // Don't add to errors array - grades import is optional
                            }
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Failed to import '" . ($assignment['title'] ?? 'Unknown') . "': " . $e->getMessage();
                    }
                }
            });

            $message = "Successfully imported {$importedCount} activities from Google Classroom.";
            if ($gradesImportedCount > 0) {
                $message .= " Also imported {$gradesImportedCount} grade(s).";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'grades_imported_count' => $gradesImportedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to import selected coursework from GCR', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import activities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate class standing for a student in a specific term
     */
    private function recalculateClassStanding(StudentMapping $studentMapping, string $term): void
    {
        // Get all grade records for this student in this term
        $gradeRecords = \App\Models\GradeRecord::whereHas('activity', function ($query) use ($studentMapping, $term) {
            $query->where('subject_id', $studentMapping->subject_id)
                  ->where('term', $term);
        })->where('student_mapping_id', $studentMapping->id)->get();

        if ($gradeRecords->isEmpty()) {
            return;
        }

        // Calculate total score and total possible score
        $totalScore = $gradeRecords->sum('score');
        $totalPossible = $gradeRecords->sum('max_score');

        if ($totalPossible == 0) {
            return;
        }

        // Get grading configuration to get the activity weight
        $gradingConfig = \App\Models\GradingConfig::where('subject_id', $studentMapping->subject_id)
            ->where('term', $term)
            ->first();
        
        // Get activity weight (default to 40% if not configured)
        $activityWeight = $gradingConfig ? $gradingConfig->class_standing_weight : 40;
        
        // Calculate class standing percentage
        $classStandingPercentage = ($totalScore / $totalPossible) * 100;

        // Apply the activity weight to get the class standing
        $classStanding = $classStandingPercentage * ($activityWeight / 100);

        // Update or create term grade record
        \App\Models\TermGrade::updateOrCreate(
            [
                'student_mapping_id' => $studentMapping->id,
                'subject_id' => $studentMapping->subject_id,
                'term' => $term,
            ],
            [
                'class_standing' => round($classStanding, 2),
            ]
        );
    }

    /**
     * Calculate name similarity for matching
     */
    private function calculateNameSimilarity($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        // Exact match
        if ($name1 === $name2) {
            return 100;
        }

        // Remove common suffixes/prefixes
        $name1 = preg_replace('/\b(jr|sr|ii|iii|iv)\b\.?/i', '', $name1);
        $name2 = preg_replace('/\b(jr|sr|ii|iii|iv)\b\.?/i', '', $name2);

        // Split into parts
        $parts1 = preg_split('/\s+/', trim($name1));
        $parts2 = preg_split('/\s+/', trim($name2));

        // Check if all parts of shorter name are in longer name
        $shorter = count($parts1) < count($parts2) ? $parts1 : $parts2;
        $longer = count($parts1) >= count($parts2) ? $parts2 : $parts1;

        $matchCount = 0;
        foreach ($shorter as $part) {
            foreach ($longer as $longPart) {
                similar_text($part, $longPart, $percent);
                if ($percent > 85) {
                    $matchCount++;
                    break;
                }
            }
        }

        return ($matchCount / count($shorter)) * 100;
    }

    private function syncActivityToGradingItem($activity)
    {
        $gradingClass = \App\Models\GradingClass::where('subject_id', $activity->subject_id)
            ->where('term', $activity->term)
            ->first();

        if (!$gradingClass) return;

        // Try to find matching component by name
        $componentName = match($activity->activity_category) {
            'quiz' => 'Quizzes',
            'activity' => 'Activities',
            default => 'Activities'
        };

        $component = $gradingClass->components()->where('component_name', $componentName)->first();
        
        // If no matching component, try to add to "Class Standing" component
        if (!$component) {
            $component = $gradingClass->components()->where('component_name', 'Class Standing')->first();
        }
        
        if (!$component) return;

        // Check if item already exists
        $existingItem = \App\Models\ComponentItem::where('component_id', $component->id)
            ->where('activity_id', $activity->id)
            ->first();
            
        if ($existingItem) return;

        \App\Models\ComponentItem::create([
            'component_id' => $component->id,
            'item_name' => $activity->name,
            'max_score' => $activity->max_score,
            'date' => now(),
            'activity_id' => $activity->id
        ]);
    }

    /**
     * Sync course states from Google Classroom
     * This updates the gcr_course_state for all connected subjects
     */
    public function syncCourseStates(Request $request): JsonResponse
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            // Get all connected subjects for this faculty
            $connectedSubjects = Subject::where('faculty_id', $faculty->id)
                ->active()
                ->whereNotNull('gcr_class_id')
                ->get();

            if ($connectedSubjects->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No connected subjects to sync.',
                    'synced_count' => 0,
                    'archived_count' => 0
                ]);
            }

            // Fetch all courses including archived ones
            $courses = $this->classroomService->getCourses(true);
            $coursesById = collect($courses)->keyBy('id');

            $syncedCount = 0;
            $archivedCount = 0;
            $errors = [];

            foreach ($connectedSubjects as $subject) {
                $course = $coursesById->get($subject->gcr_class_id);
                
                if ($course) {
                    $newState = $course['course_state'];
                    $oldState = $subject->gcr_course_state;
                    
                    if ($oldState !== $newState) {
                        $subject->update(['gcr_course_state' => $newState]);
                        $syncedCount++;
                        
                        if ($newState === 'ARCHIVED') {
                            $archivedCount++;
                            Log::info('Subject archived from GCR sync', [
                                'subject_id' => $subject->id,
                                'subject_code' => $subject->subject_code,
                                'gcr_class_id' => $subject->gcr_class_id
                            ]);
                        }
                    }
                } else {
                    // Course not found in GCR - might have been deleted
                    $errors[] = "Course not found for subject: {$subject->subject_code}";
                }
            }

            $message = "Synced course states for {$syncedCount} subject(s).";
            if ($archivedCount > 0) {
                $message .= " {$archivedCount} subject(s) were archived.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'synced_count' => $syncedCount,
                'archived_count' => $archivedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync course states from GCR', [
                'faculty_id' => $faculty->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync course states: ' . $e->getMessage()
            ], 500);
        }
    }
}