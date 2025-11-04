<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\SchoolSubjectAssignment;
use App\Models\Faculty;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubjectService
{
    public function __construct(
        private SchoolDatabaseService $schoolDatabaseService
    ) {}

    /**
     * Get current semester subjects for a faculty member
     */
    public function getCurrentSemesterSubjects(int $facultyId): Collection
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        // Determine current semester based on month
        $currentSemester = $this->getCurrentSemester($currentMonth);
        $academicYear = $this->getAcademicYear($currentYear, $currentMonth);

        return Subject::where('faculty_id', $facultyId)
            ->where('academic_year', $academicYear)
            ->where('semester', $currentSemester)
            ->with(['activities'])
            ->orderBy('subject_code')
            ->orderBy('section')
            ->get();
    }

    /**
     * Get past semester subjects for a faculty member
     */
    public function getPastSemesterSubjects(int $facultyId): Collection
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        $currentSemester = $this->getCurrentSemester($currentMonth);
        $academicYear = $this->getAcademicYear($currentYear, $currentMonth);

        return Subject::where('faculty_id', $facultyId)
            ->where(function ($query) use ($academicYear, $currentSemester) {
                $query->where('academic_year', '<', $academicYear)
                    ->orWhere(function ($q) use ($academicYear, $currentSemester) {
                        $q->where('academic_year', $academicYear)
                          ->where('semester', '<>', $currentSemester);
                    });
            })
            ->with(['activities'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->orderBy('subject_code')
            ->orderBy('section')
            ->get()
            ->groupBy(['academic_year', 'semester']);
    }

    /**
     * Sync subjects from school database for a faculty member
     */
    public function syncSubjectsFromSchoolDatabase(int $facultyId): int
    {
        $faculty = Faculty::findOrFail($facultyId);
        
        if (!$faculty->school_faculty_id) {
            throw new \Exception('Faculty member is not linked to school database');
        }

        // Get assignments from school database
        $schoolAssignments = $this->schoolDatabaseService->getFacultyAssignments($faculty->school_faculty_id);
        
        $syncedCount = 0;

        foreach ($schoolAssignments as $assignment) {
            $existingSubject = Subject::where('school_subject_id', $assignment->id)
                ->where('faculty_id', $facultyId)
                ->first();

            if (!$existingSubject) {
                Subject::create([
                    'school_subject_id' => $assignment->id,
                    'faculty_id' => $facultyId,
                    'subject_code' => $assignment->subject_code,
                    'subject_name' => $assignment->subject_name,
                    'section' => $assignment->section,
                    'type' => $this->determineSubjectType($assignment->subject_code, $assignment->subject_name),
                    'academic_year' => $assignment->academic_year,
                    'semester' => $assignment->semester,
                ]);
                
                $syncedCount++;
            }
        }

        return $syncedCount;
    }

    /**
     * Configure subject type (lecture_only or lecture_lab)
     */
    public function configureSubjectType(Subject $subject, string $type): Subject
    {
        if (!in_array($type, ['lecture_only', 'lecture_lab'])) {
            throw new \InvalidArgumentException('Invalid subject type');
        }

        $subject->update(['type' => $type]);
        
        return $subject->fresh();
    }

    /**
     * Get subject metadata including activity counts and grade status
     */
    public function getSubjectMetadata(Subject $subject): array
    {
        $lectureActivities = $subject->activities()->where('type', 'lecture')->count();
        $labActivities = $subject->activities()->where('type', 'lab')->count();
        
        $studentCount = $subject->studentMappings()->count();
        
        $gradeProgress = $this->calculateGradeProgress($subject);

        return [
            'lecture_activities' => $lectureActivities,
            'lab_activities' => $labActivities,
            'total_activities' => $lectureActivities + $labActivities,
            'student_count' => $studentCount,
            'grade_progress' => $gradeProgress,
            'has_gcr_integration' => !is_null($subject->gcr_class_id),
        ];
    }

    /**
     * Determine subject type based on subject code and name
     */
    private function determineSubjectType(string $subjectCode, string $subjectName): string
    {
        // Common patterns for lab subjects
        $labPatterns = [
            '/lab/i',
            '/laboratory/i',
            '/practicum/i',
            '/workshop/i',
            '/L$/i', // Ends with L
        ];

        foreach ($labPatterns as $pattern) {
            if (preg_match($pattern, $subjectCode) || preg_match($pattern, $subjectName)) {
                return 'lecture_lab';
            }
        }

        return 'lecture_only';
    }

    /**
     * Get current semester based on month
     */
    private function getCurrentSemester(int $month): string
    {
        if ($month >= 8 && $month <= 12) {
            return '1st';
        } elseif ($month >= 1 && $month <= 5) {
            return '2nd';
        } else {
            return 'Summer';
        }
    }

    /**
     * Get academic year based on current year and month
     */
    private function getAcademicYear(int $year, int $month): string
    {
        if ($month >= 6) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    /**
     * Calculate grade progress for a subject
     */
    private function calculateGradeProgress(Subject $subject): array
    {
        // This will be expanded when grade records are implemented
        return [
            'prelim' => 0,
            'midterm' => 0,
            'finals' => 0,
            'overall' => 0,
        ];
    }
}