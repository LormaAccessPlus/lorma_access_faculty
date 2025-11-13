<?php

namespace App\Services;

use App\Models\TermGrade;
use App\Models\SchoolTermGrade;
use App\Models\Subject;
use App\Models\StudentMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GradeSyncService
{
    /**
     * Sync a single term grade to the school database
     */
    public function syncTermGrade(TermGrade $termGrade): bool
    {
        try {
            $studentMapping = $termGrade->studentMapping;
            $subject = $termGrade->subject;

            // Validate required data
            if (!$studentMapping->school_student_id) {
                Log::warning('Cannot sync grade: Student not mapped to school database', [
                    'term_grade_id' => $termGrade->id,
                    'student_mapping_id' => $studentMapping->id
                ]);
                return false;
            }

            if (!$subject->school_schedule_code) {
                Log::warning('Cannot sync grade: Subject not mapped to school schedule', [
                    'term_grade_id' => $termGrade->id,
                    'subject_id' => $subject->id
                ]);
                return false;
            }

            // Get StudID from school database
            $schoolStudent = DB::connection('school_db')
                ->table('students')
                ->where('ID', $studentMapping->school_student_id)
                ->first();

            if (!$schoolStudent) {
                Log::warning('Cannot sync grade: School student not found', [
                    'school_student_id' => $studentMapping->school_student_id
                ]);
                return false;
            }

            // Map term names
            $termMap = [
                'prelim' => 'Prelim',
                'midterm' => 'Midterm',
                'finals' => 'Finals'
            ];

            $schoolTerm = $termMap[$termGrade->term] ?? null;
            if (!$schoolTerm) {
                Log::error('Invalid term', ['term' => $termGrade->term]);
                return false;
            }

            // Prepare grade data
            $gradeField = $termGrade->term === 'prelim' ? 'PrelimGrade' :
                         ($termGrade->term === 'midterm' ? 'MidtermGrade' : 'FinalsGrade');
            
            $dateField = $termGrade->term === 'prelim' ? 'PrelimDate' :
                        ($termGrade->term === 'midterm' ? 'MidtermDate' : 'FinalsDate');

            // Find or create the school term grade record
            $schoolTermGrade = SchoolTermGrade::firstOrNew([
                'StudID' => $schoolStudent->StudID,
                'CodeNumber' => $subject->school_schedule_code,
                'Term' => $schoolTerm,
                'SchoolYear' => $subject->academic_year
            ]);

            // Update the specific term grade
            $schoolTermGrade->{$gradeField} = number_format($termGrade->term_grade, 2);
            $schoolTermGrade->{$dateField} = now();
            $schoolTermGrade->LastUpdate = now();
            $schoolTermGrade->isRemedial = 0;

            $schoolTermGrade->save();

            Log::info('Grade synced successfully', [
                'term_grade_id' => $termGrade->id,
                'school_term_grade_id' => $schoolTermGrade->ID,
                'grade' => $termGrade->term_grade
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync grade to school database', [
                'term_grade_id' => $termGrade->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Sync all term grades for a subject
     */
    public function syncSubjectGrades(Subject $subject): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->whereNotNull('term_grade')
            ->with(['studentMapping', 'subject'])
            ->get();

        foreach ($termGrades as $termGrade) {
            if ($this->syncTermGrade($termGrade)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Sync all term grades for a specific student in a subject
     */
    public function syncStudentGrades(StudentMapping $studentMapping): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $termGrades = TermGrade::where('student_mapping_id', $studentMapping->id)
            ->whereNotNull('term_grade')
            ->with(['studentMapping', 'subject'])
            ->get();

        foreach ($termGrades as $termGrade) {
            if ($this->syncTermGrade($termGrade)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Sync a specific term for all students in a subject
     */
    public function syncSubjectTermGrades(Subject $subject, string $term): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->where('term', $term)
            ->whereNotNull('term_grade')
            ->with(['studentMapping', 'subject'])
            ->get();

        foreach ($termGrades as $termGrade) {
            if ($this->syncTermGrade($termGrade)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}
