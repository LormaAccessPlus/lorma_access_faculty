<?php

namespace App\Services;

use App\Models\FinalRating;
use App\Models\StudentMapping;
use App\Models\Subject;
use App\Models\TermGrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class GradeStorageService
{
    /**
     * Store grades in both application and school databases with transaction management
     */
    public function storeGradesWithSync(Subject $subject, $finalRatings, string $academicYear, string $semester): array
    {
        $results = [
            'success' => false,
            'stored_count' => 0,
            'failed_count' => 0,
            'errors' => [],
            'warnings' => []
        ];

        // Start transaction on both databases
        DB::beginTransaction();
        
        try {
            DB::connection('school_db')->beginTransaction();
            
            // Ensure we have a collection or array to iterate over
            if (is_object($finalRatings) && method_exists($finalRatings, 'toArray')) {
                // It's a collection, keep it as is for iteration
                $ratingsToProcess = $finalRatings;
            } elseif (is_array($finalRatings)) {
                // It's already an array, convert back to models if needed
                $ratingsToProcess = collect($finalRatings)->map(function ($rating) {
                    return is_array($rating) ? new FinalRating($rating) : $rating;
                });
            } else {
                throw new Exception('Invalid final ratings data structure');
            }
            
            foreach ($ratingsToProcess as $finalRating) {
                try {
                    // Ensure we have a FinalRating model object
                    if (!($finalRating instanceof FinalRating)) {
                        throw new Exception('Expected FinalRating model object, got ' . gettype($finalRating));
                    }
                    
                    $this->storeSingleGradeWithSync($finalRating, $subject, $academicYear, $semester);
                    $results['stored_count']++;
                } catch (Exception $e) {
                    $results['failed_count']++;
                    $results['errors'][] = [
                        'student_mapping_id' => $finalRating->student_mapping_id ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to store grade for student', [
                        'student_mapping_id' => $finalRating->student_mapping_id ?? 'unknown',
                        'subject_id' => $subject->id,
                        'error' => $e->getMessage(),
                        'final_rating_type' => gettype($finalRating)
                    ]);
                }
            }
            
            // Commit both transactions if no critical errors
            if ($results['failed_count'] === 0) {
                DB::commit();
                DB::connection('school_db')->commit();
                $results['success'] = true;
            } else {
                // Rollback if there were any failures
                DB::rollBack();
                DB::connection('school_db')->rollBack();
                $results['errors'][] = 'Transaction rolled back due to failures';
            }
            
        } catch (Throwable $e) {
            // Rollback both transactions on any error
            DB::rollBack();
            DB::connection('school_db')->rollBack();
            
            $results['errors'][] = 'Critical error: ' . $e->getMessage();
            
            Log::error('Critical error in grade storage transaction', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $results;
    }

    /**
     * Store a single grade record in both databases
     */
    private function storeSingleGradeWithSync(FinalRating $finalRating, Subject $subject, string $academicYear, string $semester): void
    {
        // Validate the final rating data
        $this->validateFinalRating($finalRating);
        
        // Get student mapping to find school database student ID
        $studentMapping = StudentMapping::with('subject')->find($finalRating->student_mapping_id);
        
        if (!$studentMapping) {
            throw new Exception("Student mapping not found for ID: {$finalRating->student_mapping_id}");
        }

        // Validate that the student mapping has a school student ID
        if (empty($studentMapping->school_student_id)) {
            throw new Exception("Student '{$studentMapping->student_name}' is not mapped to a school database student. Please complete student mapping first.");
        }

        // Insert into school database termgrades table
        $this->insertIntoSchoolDatabase($studentMapping, $subject, $finalRating, $academicYear, $semester);
        
        // Log the successful insertion
        Log::info('Grade successfully stored in school database', [
            'student_mapping_id' => $finalRating->student_mapping_id,
            'school_student_id' => $studentMapping->school_student_id,
            'subject_id' => $subject->id,
            'final_rating' => $finalRating->final_rating
        ]);
    }

    /**
     * Insert grade into school database termgrades table
     */
    private function insertIntoSchoolDatabase(StudentMapping $studentMapping, Subject $subject, FinalRating $finalRating, string $academicYear, string $semester): void
    {
        // Double-check that we have a valid school student ID
        if (empty($studentMapping->school_student_id)) {
            throw new Exception("Cannot sync grades: Student '{$studentMapping->student_name}' (ID: {$studentMapping->id}) is not mapped to a school database student.");
        }

        // Generate CodeNumber based on subject and academic period
        $codeNumber = $this->generateCodeNumber($subject, $semester, $academicYear);
        
        // Prepare grade data for school database
        $gradeData = [
            'StudID' => $studentMapping->school_student_id,
            'CodeNumber' => $codeNumber,
            'Term' => $this->convertSemesterToTerm($semester),
            'SchoolYear' => $academicYear,
            'PrelimGrade' => $this->formatGradeForSchoolDB($finalRating->prelim_grade),
            'MidtermGrade' => $this->formatGradeForSchoolDB($finalRating->midterm_grade),
            'FinalsGrade' => $this->formatGradeForSchoolDB($finalRating->finals_grade),
            'PreFinalsGrade' => null, // Not used in our system
            'isRemedial' => 0,
            'LastUpdate' => now()
        ];

        // Log the data being inserted for debugging
        Log::debug('Inserting grade data into school database', [
            'student_name' => $studentMapping->student_name,
            'school_student_id' => $studentMapping->school_student_id,
            'code_number' => $codeNumber,
            'grade_data' => $gradeData
        ]);

        // Insert or update the grade record in school database
        DB::connection('school_db')->table('termgrades')
            ->updateOrInsert(
                [
                    'StudID' => $gradeData['StudID'],
                    'CodeNumber' => $gradeData['CodeNumber'],
                    'Term' => $gradeData['Term'],
                    'SchoolYear' => $gradeData['SchoolYear']
                ],
                $gradeData
            );
    }

    /**
     * Validate final rating data before storage
     */
    private function validateFinalRating(FinalRating $finalRating): void
    {
        $errors = [];

        // Check for required fields
        if (empty($finalRating->student_mapping_id)) {
            $errors[] = 'Student mapping ID is required';
        }

        if (empty($finalRating->subject_id)) {
            $errors[] = 'Subject ID is required';
        }

        // Validate grade ranges (assuming 0-100 scale)
        $grades = [
            'prelim_grade' => $finalRating->prelim_grade,
            'midterm_grade' => $finalRating->midterm_grade,
            'finals_grade' => $finalRating->finals_grade,
            'final_rating' => $finalRating->final_rating
        ];

        foreach ($grades as $gradeType => $grade) {
            if ($grade !== null && ($grade < 0 || $grade > 100)) {
                $errors[] = "{$gradeType} must be between 0 and 100";
            }
        }

        if (!empty($errors)) {
            throw new Exception('Validation failed: ' . implode(', ', $errors));
        }
    }

    /**
     * Generate CodeNumber for school database based on subject and academic period
     */
    private function generateCodeNumber(Subject $subject, string $semester, string $academicYear): string
    {
        // Format: SUBJECTCODE + SEMESTER + YEAR (max 12 chars)
        // Example: "SOFTENG125" for SOFTENG, semester 1, year 2025
        $semesterNum = $this->convertSemesterToNumber($semester);
        $year = substr($academicYear, -2); // Extract last 2 digits from "2024-2025"
        
        // Truncate subject code if needed to fit in 12 characters
        $maxSubjectLength = 12 - 3; // Reserve 3 chars for semester + year
        $subjectCode = substr($subject->subject_code, 0, $maxSubjectLength);
        
        return $subjectCode . $semesterNum . $year;
    }

    /**
     * Convert semester to single digit number
     */
    private function convertSemesterToNumber(string $semester): string
    {
        $semesterMap = [
            '1' => '1',
            '2' => '2',
            '3' => '3',
            'first' => '1',
            'second' => '2',
            'summer' => '3'
        ];

        return $semesterMap[strtolower($semester)] ?? '1';
    }

    /**
     * Convert semester number to term string for school database
     */
    private function convertSemesterToTerm(string $semester): string
    {
        $termMap = [
            '1' => '1st',
            '2' => '2nd',
            '3' => 'Summer',
            'first' => '1st',
            'second' => '2nd',
            'summer' => 'Summer'
        ];

        return $termMap[strtolower($semester)] ?? $semester;
    }

    /**
     * Format grade for school database storage
     */
    private function formatGradeForSchoolDB(?float $grade): ?string
    {
        if ($grade === null) {
            return null;
        }

        // Format to 2 decimal places and return as string
        return number_format($grade, 2);
    }

    /**
     * Sync grades from application database to school database
     */
    public function syncGradesToSchoolDatabase(Subject $subject, string $academicYear, string $semester): array
    {
        $results = [
            'success' => false,
            'synced_count' => 0,
            'failed_count' => 0,
            'errors' => []
        ];

        try {
            // Check if all students are properly mapped first
            $unmappedStudents = StudentMapping::where('subject_id', $subject->id)
                ->whereNull('school_student_id')
                ->get();

            if ($unmappedStudents->isNotEmpty()) {
                $unmappedNames = $unmappedStudents->pluck('student_name')->join(', ');
                $results['errors'][] = "Cannot sync grades: {$unmappedStudents->count()} students are not mapped to school database: {$unmappedNames}. Please complete student mapping first.";
                return $results;
            }

            // Get all final ratings for the subject
            $finalRatings = FinalRating::where('subject_id', $subject->id)
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->get();

            if ($finalRatings->isEmpty()) {
                $results['errors'][] = 'No final ratings found for synchronization. Please ensure grades have been calculated for all terms.';
                return $results;
            }

            // Sync each final rating
            $results = $this->storeGradesWithSync($subject, $finalRatings, $academicYear, $semester);

        } catch (Exception $e) {
            $results['errors'][] = 'Sync failed: ' . $e->getMessage();
            
            Log::error('Grade synchronization failed', [
                'subject_id' => $subject->id,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Verify grade synchronization between databases
     */
    public function verifyGradeSynchronization(Subject $subject, string $academicYear, string $semester): array
    {
        $results = [
            'synchronized' => true,
            'discrepancies' => [],
            'missing_in_school_db' => [],
            'app_db_count' => 0,
            'school_db_count' => 0
        ];

        try {
            // Get final ratings from application database
            $appFinalRatings = FinalRating::with('studentMapping')
                ->where('subject_id', $subject->id)
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->get();

            $results['app_db_count'] = $appFinalRatings->count();

            // Get corresponding records from school database
            $codeNumber = $this->generateCodeNumber($subject, $semester, $academicYear);
            $term = $this->convertSemesterToTerm($semester);

            $schoolGrades = DB::connection('school_db')
                ->table('termgrades')
                ->where('CodeNumber', $codeNumber)
                ->where('Term', $term)
                ->where('SchoolYear', $academicYear)
                ->get()
                ->keyBy('StudID');

            $results['school_db_count'] = $schoolGrades->count();

            // Compare records
            foreach ($appFinalRatings as $finalRating) {
                $studentMapping = $finalRating->studentMapping;
                $schoolGrade = $schoolGrades->get($studentMapping->school_student_id);

                if (!$schoolGrade) {
                    $results['missing_in_school_db'][] = [
                        'student_mapping_id' => $finalRating->student_mapping_id,
                        'school_student_id' => $studentMapping->school_student_id,
                        'student_name' => $studentMapping->student_name
                    ];
                    $results['synchronized'] = false;
                } else {
                    // Check for discrepancies
                    $discrepancies = $this->compareGrades($finalRating, $schoolGrade);
                    if (!empty($discrepancies)) {
                        $results['discrepancies'][] = [
                            'student_mapping_id' => $finalRating->student_mapping_id,
                            'school_student_id' => $studentMapping->school_student_id,
                            'student_name' => $studentMapping->student_name,
                            'differences' => $discrepancies
                        ];
                        $results['synchronized'] = false;
                    }
                }
            }

        } catch (Exception $e) {
            $results['synchronized'] = false;
            $results['error'] = 'Verification failed: ' . $e->getMessage();
            
            Log::error('Grade synchronization verification failed', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Compare grades between application and school databases
     */
    private function compareGrades(FinalRating $finalRating, object $schoolGrade): array
    {
        $discrepancies = [];

        $comparisons = [
            'prelim_grade' => 'PrelimGrade',
            'midterm_grade' => 'MidtermGrade',
            'finals_grade' => 'FinalsGrade'
        ];

        foreach ($comparisons as $appField => $schoolField) {
            $appValue = $this->formatGradeForSchoolDB($finalRating->$appField);
            $schoolValue = $schoolGrade->$schoolField;

            if ($appValue !== $schoolValue) {
                $discrepancies[] = [
                    'field' => $appField,
                    'app_value' => $appValue,
                    'school_value' => $schoolValue
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Get grade storage statistics
     */
    public function getGradeStorageStatistics(Subject $subject, string $academicYear, string $semester): array
    {
        $stats = [
            'total_students' => 0,
            'grades_computed' => 0,
            'grades_synced' => 0,
            'pending_sync' => 0,
            'sync_percentage' => 0
        ];

        try {
            // Count total students mapped to this subject
            $stats['total_students'] = StudentMapping::where('subject_id', $subject->id)->count();

            // Count computed final ratings
            $stats['grades_computed'] = FinalRating::where('subject_id', $subject->id)
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->count();

            // Verify sync status
            $verification = $this->verifyGradeSynchronization($subject, $academicYear, $semester);
            $stats['grades_synced'] = $verification['school_db_count'];
            $stats['pending_sync'] = $stats['grades_computed'] - $stats['grades_synced'];

            // Calculate sync percentage
            if ($stats['grades_computed'] > 0) {
                $stats['sync_percentage'] = round(($stats['grades_synced'] / $stats['grades_computed']) * 100, 2);
            }

        } catch (Exception $e) {
            Log::error('Failed to get grade storage statistics', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
    }

    /**
     * Test database connections
     */
    public function testDatabaseConnections(): array
    {
        $results = [
            'app_db' => ['connected' => false, 'error' => null],
            'school_db' => ['connected' => false, 'error' => null]
        ];

        // Test application database
        try {
            DB::connection()->getPdo();
            $results['app_db']['connected'] = true;
        } catch (Exception $e) {
            $results['app_db']['error'] = $e->getMessage();
        }

        // Test school database
        try {
            DB::connection('school_db')->getPdo();
            $results['school_db']['connected'] = true;
        } catch (Exception $e) {
            $results['school_db']['error'] = $e->getMessage();
        }

        return $results;
    }
}