<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\GradeStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class GradeSyncController extends Controller
{
    private GradeStorageService $gradeStorageService;

    public function __construct(GradeStorageService $gradeStorageService)
    {
        $this->gradeStorageService = $gradeStorageService;
    }

    /**
     * Display the grade synchronization interface
     */
    public function index(Subject $subject): View
    {
        $academicYear = $subject->academic_year ?? config('app.current_academic_year', date('Y'));
        $semester = $subject->semester ?? config('app.current_semester', '1');

        // Get synchronization statistics
        $stats = $this->gradeStorageService->getGradeStorageStatistics($subject, $academicYear, $semester);
        
        // Verify current synchronization status
        $verification = $this->gradeStorageService->verifyGradeSynchronization($subject, $academicYear, $semester);
        
        // Test database connections
        $connectionStatus = $this->gradeStorageService->testDatabaseConnections();

        return view('grades.sync', compact('subject', 'stats', 'verification', 'connectionStatus', 'academicYear', 'semester'));
    }

    /**
     * Synchronize grades to school database
     */
    public function sync(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'required|string',
            'confirm' => 'required|boolean|accepted'
        ]);

        try {
            $academicYear = $request->input('academic_year');
            $semester = $request->input('semester');

            // Perform the synchronization
            $results = $this->gradeStorageService->syncGradesToSchoolDatabase($subject, $academicYear, $semester);

            if ($results['success']) {
                Log::info('Grade synchronization completed successfully', [
                    'subject_id' => $subject->id,
                    'academic_year' => $academicYear,
                    'semester' => $semester,
                    'synced_count' => $results['stored_count']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully synchronized {$results['stored_count']} grade records to school database.",
                    'data' => $results
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade synchronization completed with errors.',
                    'data' => $results
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Grade synchronization failed', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Grade synchronization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify grade synchronization status
     */
    public function verify(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'required|string'
        ]);

        try {
            $academicYear = $request->input('academic_year');
            $semester = $request->input('semester');

            $verification = $this->gradeStorageService->verifyGradeSynchronization($subject, $academicYear, $semester);

            return response()->json([
                'success' => true,
                'data' => $verification
            ]);

        } catch (\Exception $e) {
            Log::error('Grade verification failed', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Grade verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get grade storage statistics
     */
    public function statistics(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'required|string'
        ]);

        try {
            $academicYear = $request->input('academic_year');
            $semester = $request->input('semester');

            $stats = $this->gradeStorageService->getGradeStorageStatistics($subject, $academicYear, $semester);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get grade statistics', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test database connections
     */
    public function testConnections(): JsonResponse
    {
        try {
            $connectionStatus = $this->gradeStorageService->testDatabaseConnections();

            return response()->json([
                'success' => true,
                'data' => $connectionStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Database connection test failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview grades before synchronization
     */
    public function preview(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'required|string'
        ]);

        try {
            $academicYear = $request->input('academic_year');
            $semester = $request->input('semester');

            // Get final ratings that would be synchronized
            $finalRatings = \App\Models\FinalRating::with('studentMapping')
                ->where('subject_id', $subject->id)
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->get();

            $preview = $finalRatings->map(function ($finalRating) {
                return [
                    'student_name' => $finalRating->studentMapping->student_name,
                    'school_student_id' => $finalRating->studentMapping->school_student_id,
                    'prelim_grade' => $finalRating->prelim_grade,
                    'midterm_grade' => $finalRating->midterm_grade,
                    'finals_grade' => $finalRating->finals_grade,
                    'final_rating' => $finalRating->final_rating
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $preview->count(),
                    'grades' => $preview
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Grade preview failed', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Grade preview failed: ' . $e->getMessage()
            ], 500);
        }
    }
}