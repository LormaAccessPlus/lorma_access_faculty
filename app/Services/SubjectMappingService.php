<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\StudentMapping;
use App\Models\Faculty;
use App\Services\GoogleClassroomService;
use App\Services\SchoolDatabaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SubjectMappingService
{
    private GoogleClassroomService $googleClassroomService;
    private SchoolDatabaseService $schoolDatabaseService;

    public function __construct(
        GoogleClassroomService $googleClassroomService,
        SchoolDatabaseService $schoolDatabaseService
    ) {
        $this->googleClassroomService = $googleClassroomService;
        $this->schoolDatabaseService = $schoolDatabaseService;
    }

    /**
     * Get available Google Classroom courses for mapping
     */
    public function getAvailableGoogleClassroomCourses(int $facultyId): array
    {
        try {
            // Get faculty and authenticate with Google
            $faculty = Faculty::find($facultyId);
            if (!$faculty) {
                throw new Exception('Faculty not found');
            }
            
            // Authenticate with Google Classroom
            if (!$this->googleClassroomService->authenticateWithFaculty($faculty)) {
                throw new Exception('Failed to authenticate with Google Classroom');
            }
            
            $courses = $this->googleClassroomService->getCourses();
            
            // Filter out courses that are already mapped
            $mappedCourseIds = Subject::whereNotNull('gcr_class_id')
                ->pluck('gcr_class_id')
                ->toArray();
            
            return array_filter($courses, function($course) use ($mappedCourseIds) {
                return !in_array($course['id'], $mappedCourseIds);
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get Google Classroom courses', [
                'faculty_id' => $facultyId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get available school database subjects for mapping
     */
    public function getAvailableSchoolSubjects(Faculty $faculty, string $academicYear, string $semester): array
    {
        try {
            // Check if faculty is linked to school database
            if (!$faculty->school_faculty_id) {
                Log::warning('Faculty not linked to school database', [
                    'faculty_id' => $faculty->id,
                    'email' => $faculty->email
                ]);
                return [];
            }
            
            // Get faculty assignments from school database
            $assignments = $this->schoolDatabaseService->getFacultyAssignments($faculty->school_faculty_id);
            
            // Convert assignments to the expected format and filter by academic period
            $schoolSubjects = [];
            foreach ($assignments as $assignment) {
                // Filter by academic year and semester
                if ($assignment->academic_year === $academicYear && $assignment->semester === $semester) {
                    $schoolSubjects[] = [
                        'subject_code' => $assignment->subject_code,
                        'subject_name' => $assignment->subject_name,
                        'section' => $assignment->section,
                        'schedule_code' => $assignment->section, // Use section as schedule code
                        'academic_year' => $assignment->academic_year,
                        'semester' => $assignment->semester,
                        'assignment_id' => $assignment->id
                    ];
                }
            }
            
            // Filter out subjects that are already mapped
            $mappedSchoolSubjects = Subject::whereNotNull('school_subject_code')
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->pluck('school_subject_code')
                ->toArray();
            
            return array_filter($schoolSubjects, function($subject) use ($mappedSchoolSubjects) {
                return !in_array($subject['subject_code'], $mappedSchoolSubjects);
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get school database subjects', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Create subject mapping between Google Classroom and School Database
     */
    public function createSubjectMapping(array $mappingData): array
    {
        DB::beginTransaction();
        
        try {
            // Validate mapping data
            $this->validateMappingData($mappingData);
            
            // Get Google Classroom course details
            $gcrCourse = $this->getCourse($mappingData['gcr_class_id']);
            
            // Get school database subject details
            $schoolSubject = $this->schoolDatabaseService->getSubjectDetails(
                $mappingData['school_subject_code'],
                $mappingData['academic_year'],
                $mappingData['semester']
            );
            
            // Create the subject in main database
            $subject = Subject::create([
                'faculty_id' => $mappingData['faculty_id'],
                'gcr_class_id' => $mappingData['gcr_class_id'],
                'gcr_class_name' => $gcrCourse['name'],
                'school_subject_code' => $mappingData['school_subject_code'],
                'school_subject_name' => $schoolSubject['subject_name'],
                'school_schedule_code' => $schoolSubject['schedule_code'],
                'subject_code' => $mappingData['school_subject_code'], // Use school subject code
                'subject_name' => $schoolSubject['subject_name'],
                'section' => $schoolSubject['section'] ?? 'N/A',
                'academic_year' => $mappingData['academic_year'],
                'semester' => $mappingData['semester'],
                'type' => 'mapped', // Indicate this is a mapped subject
                'mapping_status' => 'pending_student_mapping',
                'mapping_notes' => $mappingData['notes'] ?? null
            ]);
            
            DB::commit();
            
            Log::info('Subject mapping created successfully', [
                'subject_id' => $subject->id,
                'gcr_class_id' => $mappingData['gcr_class_id'],
                'school_subject_code' => $mappingData['school_subject_code']
            ]);
            
            return [
                'success' => true,
                'subject' => $subject,
                'message' => 'Subject mapping created successfully'
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create subject mapping', [
                'mapping_data' => $mappingData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get mapping statistics for dashboard
     */
    public function getMappingStatistics(int $facultyId): array
    {
        $stats = [
            'total_subjects' => 0,
            'pending_mapping' => 0,
            'completed_mapping' => 0,
            'total_student_mappings' => 0,
            'recent_mappings' => []
        ];
        
        try {
            $subjects = Subject::where('faculty_id', $facultyId)->get();
            
            $stats['total_subjects'] = $subjects->count();
            $stats['pending_mapping'] = $subjects->where('mapping_status', 'pending_student_mapping')->count();
            $stats['completed_mapping'] = $subjects->where('mapping_status', 'completed')->count();
            $stats['total_student_mappings'] = StudentMapping::whereIn('subject_id', $subjects->pluck('id'))->count();
            
            $stats['recent_mappings'] = Subject::where('faculty_id', $facultyId)
                ->where('mapping_status', 'completed')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(['id', 'subject_name', 'section', 'updated_at', 'student_mappings_count']);
                
        } catch (Exception $e) {
            Log::error('Failed to get mapping statistics', [
                'faculty_id' => $facultyId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $stats;
    }

    /**
     * Get Google Classroom course details
     */
    public function getCourse(string $courseId): array
    {
        try {
            // For now, we'll get all courses and find the one we need
            // This could be optimized with a specific getCourse method in GoogleClassroomService
            $courses = $this->googleClassroomService->getCourses();
            
            foreach ($courses as $course) {
                if ($course['id'] === $courseId) {
                    return $course;
                }
            }
            
            throw new Exception('Course not found');
            
        } catch (Exception $e) {
            Log::error('Failed to get Google Classroom course details', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate mapping data
     */
    private function validateMappingData(array $data): void
    {
        $required = ['faculty_id', 'gcr_class_id', 'school_subject_code', 'academic_year', 'semester'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Required field '{$field}' is missing");
            }
        }
        
        // Check if mapping already exists
        $existingMapping = Subject::where('gcr_class_id', $data['gcr_class_id'])
            ->orWhere(function($query) use ($data) {
                $query->where('school_subject_code', $data['school_subject_code'])
                      ->where('academic_year', $data['academic_year'])
                      ->where('semester', $data['semester']);
            })
            ->first();
            
        if ($existingMapping) {
            throw new Exception('This mapping already exists');
        }
    }
}