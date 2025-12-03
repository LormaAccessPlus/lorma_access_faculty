<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\ComprehensiveExam;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ComprehensiveExamController extends Controller
{
    /**
     * Display comprehensive exam scores for a subject
     */
    public function index(Subject $subject): View
    {
        $subject->load('studentMappings');
        
        // Get comprehensive exam scores (JSON approach)
        $comprehensiveExamScores = $subject->comprehensive_exam_scores ?? [];
        
        // Or get from table (uncomment if using table approach)
        // $comprehensiveExams = ComprehensiveExam::where('subject_id', $subject->id)
        //     ->with('studentMapping')
        //     ->get()
        //     ->keyBy('student_mapping_id');
        
        return view('nursing.comprehensive-exam', compact('subject', 'comprehensiveExamScores'));
    }

    /**
     * Store or update comprehensive exam scores
     */
    public function store(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            // Approach 1: Store in JSON column
            $subject->comprehensive_exam_scores = $request->scores;
            $subject->save();

            // Approach 2: Store in separate table (uncomment if preferred)
            // foreach ($request->scores as $studentMappingId => $score) {
            //     if ($score !== null) {
            //         ComprehensiveExam::updateOrCreate(
            //             [
            //                 'subject_id' => $subject->id,
            //                 'student_mapping_id' => $studentMappingId,
            //             ],
            //             [
            //                 'score' => $score,
            //                 'max_score' => 100,
            //                 'exam_date' => now(),
            //             ]
            //         );
            //     }
            // }

            return response()->json([
                'success' => true,
                'message' => 'Comprehensive exam scores saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save comprehensive exam scores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a single comprehensive exam score
     */
    public function update(Request $request, Subject $subject, int $studentMappingId): JsonResponse
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
        ]);

        try {
            // Approach 1: Update JSON column
            $scores = $subject->comprehensive_exam_scores ?? [];
            $scores[$studentMappingId] = $request->score;
            $subject->comprehensive_exam_scores = $scores;
            $subject->save();

            // Approach 2: Update in table (uncomment if preferred)
            // ComprehensiveExam::updateOrCreate(
            //     [
            //         'subject_id' => $subject->id,
            //         'student_mapping_id' => $studentMappingId,
            //     ],
            //     [
            //         'score' => $request->score,
            //         'max_score' => 100,
            //         'exam_date' => now(),
            //     ]
            // );

            return response()->json([
                'success' => true,
                'message' => 'Comprehensive exam score updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comprehensive exam score: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a comprehensive exam score
     */
    public function destroy(Subject $subject, int $studentMappingId): JsonResponse
    {
        try {
            // Approach 1: Remove from JSON column
            $scores = $subject->comprehensive_exam_scores ?? [];
            unset($scores[$studentMappingId]);
            $subject->comprehensive_exam_scores = $scores;
            $subject->save();

            // Approach 2: Delete from table (uncomment if preferred)
            // ComprehensiveExam::where('subject_id', $subject->id)
            //     ->where('student_mapping_id', $studentMappingId)
            //     ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comprehensive exam score deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comprehensive exam score: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import comprehensive exam scores from CSV
     */
    public function import(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_shift($csvData);

            $scores = [];
            foreach ($csvData as $row) {
                if (count($row) >= 2) {
                    $studentEmail = $row[0];
                    $score = floatval($row[1]);

                    // Find student mapping by email
                    $studentMapping = $subject->studentMappings()
                        ->where('student_email', $studentEmail)
                        ->first();

                    if ($studentMapping) {
                        $scores[$studentMapping->id] = $score;
                    }
                }
            }

            // Save scores
            $subject->comprehensive_exam_scores = array_merge(
                $subject->comprehensive_exam_scores ?? [],
                $scores
            );
            $subject->save();

            return response()->json([
                'success' => true,
                'message' => 'Comprehensive exam scores imported successfully!',
                'imported_count' => count($scores)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import comprehensive exam scores: ' . $e->getMessage()
            ], 500);
        }
    }
}
