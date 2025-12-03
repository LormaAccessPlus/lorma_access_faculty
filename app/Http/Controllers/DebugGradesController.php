<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Activity;
use App\Models\StudentMapping;
use App\Services\GoogleClassroomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DebugGradesController extends Controller
{
    private GoogleClassroomService $classroomService;

    public function __construct(GoogleClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }

    /**
     * Debug: Show what Google Classroom returns for a specific activity
     */
    public function debugActivity(Request $request, Subject $subject, Activity $activity)
    {
        $faculty = Auth::guard('faculty')->user();

        if (!$this->classroomService->authenticateWithFaculty($faculty)) {
            return response()->json([
                'error' => 'Failed to authenticate with Google Classroom'
            ], 401);
        }

        if (!$activity->gcr_assignment_id) {
            return response()->json([
                'error' => 'Activity is not connected to Google Classroom'
            ], 422);
        }

        try {
            // Get submissions from Google Classroom
            $submissions = $this->classroomService->getStudentSubmissions(
                $subject->gcr_class_id,
                $activity->gcr_assignment_id
            );

            // Get student mappings
            $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();

            $debug = [
                'activity' => [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'gcr_assignment_id' => $activity->gcr_assignment_id,
                    'max_score' => $activity->max_score,
                ],
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->subject_name,
                    'gcr_class_id' => $subject->gcr_class_id,
                ],
                'total_submissions' => count($submissions),
                'total_student_mappings' => $studentMappings->count(),
                'submissions' => [],
                'student_mappings' => $studentMappings->map(function($mapping) {
                    return [
                        'id' => $mapping->id,
                        'student_name' => $mapping->student_name,
                        'gcr_student_id' => $mapping->gcr_student_id,
                    ];
                })->toArray(),
            ];

            foreach ($submissions as $submission) {
                $studentMapping = $studentMappings->firstWhere('gcr_student_id', $submission['user_id']);
                
                $debug['submissions'][] = [
                    'gcr_user_id' => $submission['user_id'],
                    'student_found' => $studentMapping ? true : false,
                    'student_name' => $studentMapping ? $studentMapping->student_name : 'NOT FOUND',
                    'state' => $submission['state'],
                    'draft_grade' => $submission['draft_grade'],
                    'assigned_grade' => $submission['assigned_grade'],
                    'will_import' => ($submission['assigned_grade'] ?? $submission['draft_grade']) !== null,
                    'score_to_import' => $submission['assigned_grade'] ?? $submission['draft_grade'] ?? 'NO GRADE',
                ];
            }

            return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
