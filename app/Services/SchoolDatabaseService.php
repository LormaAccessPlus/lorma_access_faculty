<?php

namespace App\Services;

use App\Models\SchoolFaculty;
use App\Models\SchoolStudent;
use App\Models\SchoolSubjectAssignment;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchoolDatabaseService
{
    /**
     * The school database connection.
     */
    protected ConnectionInterface $connection;

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        $this->connection = DB::connection('school_db');
    }

    /**
     * Test the school database connection.
     */
    public function testConnection(): bool
    {
        try {
            $this->connection->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::error('School database connection failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find faculty member by email.
     */
    public function findFacultyByEmail(string $email): ?SchoolFaculty
    {
        try {
            return SchoolFaculty::byEmail($email)->active()->first();
        } catch (QueryException $e) {
            Log::error('Error finding faculty by email: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get current semester assignments for a faculty member.
     */
    public function getCurrentFacultyAssignments(int $facultyId): Collection
    {
        try {
            return SchoolSubjectAssignment::byFaculty($facultyId)
                ->current()
                ->active()
                ->with('faculty')
                ->orderBy('subject_code')
                ->orderBy('section')
                ->get();
        } catch (QueryException $e) {
            Log::error('Error fetching current faculty assignments: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get faculty assignments (alias for getCurrentFacultyAssignments for backward compatibility).
     */
    public function getFacultyAssignments($facultyId): Collection
    {
        // Handle both string and integer faculty IDs
        if (is_string($facultyId)) {
            // If it's a TeacherID string, we need to find the corresponding faculty record
            $faculty = SchoolFaculty::where('TeacherID', $facultyId)->first();
            if (!$faculty) {
                Log::warning('Faculty not found by TeacherID', ['teacher_id' => $facultyId]);
                return collect();
            }
            // For now, since we don't have a proper faculty_id in the teacher table,
            // we'll use a different approach - get assignments directly from teacher-related tables
            return $this->getFacultyAssignmentsByTeacherId($facultyId);
        }
        
        return $this->getCurrentFacultyAssignments((int) $facultyId);
    }

    /**
     * Get historical assignments for a faculty member.
     */
    public function getFacultyAssignmentHistory(int $facultyId, int $limit = 10): Collection
    {
        try {
            return SchoolSubjectAssignment::byFaculty($facultyId)
                ->active()
                ->with('faculty')
                ->orderBy('academic_year', 'desc')
                ->orderBy('semester', 'desc')
                ->orderBy('subject_code')
                ->limit($limit)
                ->get();
        } catch (QueryException $e) {
            Log::error('Error fetching faculty assignment history: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get assignments for a specific academic year and semester.
     */
    public function getFacultyAssignmentsByPeriod(
        int $facultyId,
        int $academicYear,
        int $semester
    ): Collection {
        try {
            return SchoolSubjectAssignment::byFaculty($facultyId)
                ->byAcademicYear($academicYear)
                ->bySemester($semester)
                ->active()
                ->with('faculty')
                ->orderBy('subject_code')
                ->orderBy('section')
                ->get();
        } catch (QueryException $e) {
            Log::error('Error fetching faculty assignments by period: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get students enrolled in a specific subject assignment.
     */
    public function getStudentsInAssignment(int $assignmentId): Collection
    {
        try {
            $assignment = SchoolSubjectAssignment::find($assignmentId);
            if (!$assignment) {
                return collect();
            }

            return $assignment->students()->where('students.status', 'active')->get();
        } catch (QueryException $e) {
            Log::error('Error fetching students in assignment: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Find student by email.
     */
    public function findStudentByEmail(string $email): ?SchoolStudent
    {
        try {
            return SchoolStudent::byEmail($email)->active()->first();
        } catch (QueryException $e) {
            Log::error('Error finding student by email: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find student by student number.
     */
    public function findStudentByNumber(string $studentNumber): ?SchoolStudent
    {
        try {
            return SchoolStudent::byStudentNumber($studentNumber)->active()->first();
        } catch (QueryException $e) {
            Log::error('Error finding student by number: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all available academic years from assignments.
     */
    public function getAvailableAcademicYears(): Collection
    {
        try {
            return SchoolSubjectAssignment::select('academic_year')
                ->distinct()
                ->orderBy('academic_year', 'desc')
                ->pluck('academic_year');
        } catch (QueryException $e) {
            Log::error('Error fetching available academic years: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get available semesters for a specific academic year.
     */
    public function getAvailableSemesters(int $academicYear): Collection
    {
        try {
            return SchoolSubjectAssignment::select('semester')
                ->where('academic_year', $academicYear)
                ->distinct()
                ->orderBy('semester')
                ->pluck('semester');
        } catch (QueryException $e) {
            Log::error('Error fetching available semesters: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get subject assignment details by ID.
     */
    public function getSubjectAssignment(int $assignmentId): ?SchoolSubjectAssignment
    {
        try {
            return SchoolSubjectAssignment::with(['faculty'])
                ->find($assignmentId);
        } catch (QueryException $e) {
            Log::error('Error fetching subject assignment: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Search for students by name or email.
     */
    public function searchStudents(string $query, int $limit = 20): Collection
    {
        try {
            return SchoolStudent::where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('student_number', 'LIKE', "%{$query}%");
            })
            ->active()
            ->limit($limit)
            ->get();
        } catch (QueryException $e) {
            Log::error('Error searching students: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get database connection statistics.
     */
    public function getConnectionStats(): array
    {
        try {
            $stats = [
                'connection_name' => 'school_db',
                'driver' => $this->connection->getDriverName(),
                'database' => $this->connection->getDatabaseName(),
                'is_connected' => $this->testConnection(),
            ];

            if ($stats['is_connected']) {
                // Get some basic table counts if connection is working
                $stats['faculty_count'] = SchoolFaculty::active()->count();
                $stats['student_count'] = SchoolStudent::active()->count();
                $stats['assignment_count'] = SchoolSubjectAssignment::active()->count();
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting connection stats: ' . $e->getMessage());
            return [
                'connection_name' => 'school_db',
                'is_connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get faculty subjects for mapping (subjects that faculty teaches)
     */
    public function getFacultySubjects($facultyIdentifier, string $academicYear, string $semester): array
    {
        try {
            $facultyId = null;
            
            // Handle different types of faculty identifiers
            if (is_numeric($facultyIdentifier)) {
                $facultyId = (int) $facultyIdentifier;
            } elseif (filter_var($facultyIdentifier, FILTER_VALIDATE_EMAIL)) {
                $faculty = $this->findFacultyByEmail($facultyIdentifier);
                if (!$faculty) {
                    Log::warning('Faculty not found by email', ['email' => $facultyIdentifier]);
                    return [];
                }
                $facultyId = $faculty->faculty_id;
            } else {
                Log::error('Invalid faculty identifier', ['identifier' => $facultyIdentifier]);
                return [];
            }
            
            // Convert academic year format (e.g., "2024-2025" to 2024)
            $academicYearInt = (int) explode('-', $academicYear)[0];
            $semesterInt = (int) $semester;
            
            $assignments = $this->getFacultyAssignmentsByPeriod($facultyId, $academicYearInt, $semesterInt);
            
            return $assignments->map(function ($assignment) {
                return [
                    'subject_code' => $assignment->subject_code,
                    'subject_name' => $assignment->subject_name,
                    'section' => $assignment->section,
                    'schedule_code' => $assignment->schedule_code ?? $assignment->subject_code . '-' . $assignment->section,
                    'academic_year' => $assignment->academic_year,
                    'semester' => $assignment->semester,
                    'assignment_id' => $assignment->id
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            Log::error('Error fetching faculty subjects for mapping', [
                'faculty_identifier' => $facultyIdentifier,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get subject details for mapping
     */
    public function getSubjectDetails(string $subjectCode, string $academicYear, string $semester): array
    {
        try {
            // Convert academic year format
            $academicYearInt = (int) explode('-', $academicYear)[0];
            $semesterInt = (int) $semester;
            
            $assignment = SchoolSubjectAssignment::where('subject_code', $subjectCode)
                ->byAcademicYear($academicYearInt)
                ->bySemester($semesterInt)
                ->active()
                ->first();
                
            if (!$assignment) {
                throw new \Exception("Subject not found: {$subjectCode}");
            }
            
            return [
                'subject_code' => $assignment->subject_code,
                'subject_name' => $assignment->subject_name,
                'section' => $assignment->section,
                'schedule_code' => $assignment->schedule_code ?? $assignment->subject_code . '-' . $assignment->section,
                'academic_year' => $assignment->academic_year,
                'semester' => $assignment->semester,
                'assignment_id' => $assignment->id
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching subject details for mapping', [
                'subject_code' => $subjectCode,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get enrolled students for a subject (for student mapping)
     */
    public function getEnrolledStudents(string $scheduleCode, string $academicYear, string $semester): array
    {
        try {
            // Convert academic year format
            $academicYearInt = (int) explode('-', $academicYear)[0];
            $semesterInt = (int) $semester;
            
            // Find the assignment by schedule code or subject code
            $assignment = SchoolSubjectAssignment::where(function($query) use ($scheduleCode) {
                $query->where('schedule_code', $scheduleCode)
                      ->orWhere('subject_code', $scheduleCode);
            })
            ->byAcademicYear($academicYearInt)
            ->bySemester($semesterInt)
            ->active()
            ->first();
            
            if (!$assignment) {
                return [];
            }
            
            $students = $this->getStudentsInAssignment($assignment->id);
            
            return $students->map(function ($student) {
                return [
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'full_name' => $student->first_name . ' ' . $student->last_name,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'status' => $student->status
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            Log::error('Error fetching enrolled students for mapping', [
                'schedule_code' => $scheduleCode,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get faculty assignments by TeacherID from the school database.
     */
    protected function getFacultyAssignmentsByTeacherId(string $teacherId): Collection
    {
        try {
            // Get assignments from schedule table (current approach based on actual DB structure)
            $schedules = $this->connection->table('schedule')
                ->join('subject', 'schedule.SubjectID', '=', 'subject.SubjectID')
                ->where('schedule.TeacherID', $teacherId)
                ->select([
                    'schedule.SchedID as id',
                    'schedule.SubjectID as subject_code',
                    'subject.Description as subject_name',
                    'schedule.CodeNumber as section',
                    'schedule.Sem as semester',
                    'schedule.SchoolYear as academic_year',
                    'schedule.Room',
                    'schedule.Day',
                    'schedule.StartTime',
                    'schedule.EndTime'
                ])
                ->get();

            // Transform to match expected format
            return $schedules->map(function ($schedule) {
                return (object) [
                    'id' => $schedule->id,
                    'subject_code' => $schedule->subject_code,
                    'subject_name' => $schedule->subject_name,
                    'section' => $schedule->section,
                    'semester' => $schedule->semester,
                    'academic_year' => $schedule->academic_year,
                    'room' => $schedule->Room,
                    'day' => $schedule->Day,
                    'start_time' => $schedule->StartTime,
                    'end_time' => $schedule->EndTime,
                ];
            });

        } catch (QueryException $e) {
            Log::error('Error fetching faculty assignments by teacher ID: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Execute a raw query on the school database (read-only).
     */
    public function executeReadOnlyQuery(string $query, array $bindings = []): Collection
    {
        try {
            // Ensure query is read-only by checking for dangerous keywords
            $dangerousKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE'];
            $upperQuery = strtoupper($query);
            
            foreach ($dangerousKeywords as $keyword) {
                if (strpos($upperQuery, $query) !== false) {
                    throw new \InvalidArgumentException("Query contains dangerous keyword: {$keyword}");
                }
            }

            $results = $this->connection->select($query, $bindings);
            return collect($results);
        } catch (\Exception $e) {
            Log::error('Error executing read-only query: ' . $e->getMessage());
            throw $e;
        }
    }
}