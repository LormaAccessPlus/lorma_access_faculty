<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Activity;
use App\Models\GradeRecord;
use App\Models\StudentMapping;
use App\Models\TermGrade;
use App\Services\GoogleClassroomService;
use App\Services\GradeSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GradeController extends Controller
{
    private GoogleClassroomService $classroomService;
    private GradeSyncService $gradeSyncService;

    public function __construct(GoogleClassroomService $classroomService, GradeSyncService $gradeSyncService)
    {
        $this->classroomService = $classroomService;
        $this->gradeSyncService = $gradeSyncService;
    }
    /**
     * Display the grade matrix for a subject
     */
    public function matrix(Subject $subject): View
    {
        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by term and type
        $activitiesByTerm = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        return view('grades.matrix', compact('subject', 'activitiesByTerm', 'gradeMatrix'));
    }

    /**
     * Display the term-based grading interface for a subject
     */
    public function termGrades(Subject $subject, string $term = 'prelim'): View
    {
        // Validate term
        $validTerms = ['prelim', 'midterm', 'finals'];
        if (!in_array($term, $validTerms)) {
            abort(404, 'Invalid term');
        }

        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) use ($term) {
                $query->where('term', $term)->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by type for the current term
        $lectureActivities = $subject->activities->where('type', 'lecture');
        $labActivities = $subject->activities->where('type', 'lab');
        
        // Get existing grade records for this term
        $gradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->whereHas('activity', function ($query) use ($term) {
            $query->where('term', $term);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // Get existing term grades
        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->where('term', $term)
            ->get()
            ->keyBy('student_mapping_id');

        // Calculate progress for each term
        $termProgress = $this->calculateTermProgress($subject);

        return view('grades.term-grades', compact(
            'subject', 
            'term', 
            'lectureActivities', 
            'labActivities', 
            'gradeMatrix', 
            'termGrades',
            'termProgress'
        ));
    }

    /**
     * Update a grade record via AJAX
     */
    public function updateGrade(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'student_mapping_id' => 'required|exists:student_mappings,id',
                'activity_id' => 'required|exists:activities,id',
                'score' => 'nullable|numeric|min:0',
            ]);

            $studentMapping = StudentMapping::findOrFail($request->student_mapping_id);
            $activity = Activity::findOrFail($request->activity_id);

            // Validate that the activity belongs to the same subject as the student mapping
            if ($activity->subject_id !== $studentMapping->subject_id) {
                return response()->json(['error' => 'Invalid activity for this subject'], 400);
            }

            // Validate score doesn't exceed max_score
            if ($request->score !== null && $request->score > $activity->max_score) {
                return response()->json(['error' => 'Score cannot exceed maximum score of ' . $activity->max_score], 400);
            }

            // Get the authenticated faculty
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            if (!$faculty) {
                return response()->json(['error' => 'Authentication required'], 401);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())], 422);
        } catch (\Exception $e) {
            \Log::error('Grade update validation error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid request data'], 400);
        }

        try {
            // Find or create grade record
            $gradeRecord = GradeRecord::updateOrCreate(
                [
                    'student_mapping_id' => $request->student_mapping_id,
                    'activity_id' => $request->activity_id,
                ],
                [
                    'score' => $request->score,
                    'max_score' => $activity->max_score,
                    'term' => $activity->term,
                    'created_by' => $faculty->id,
                ]
            );

            // Refresh the model to get the calculated percentage
            $gradeRecord->refresh();

            // Recalculate class standing for this student and term
            $this->recalculateClassStanding($studentMapping, $activity->term);

            return response()->json([
                'success' => true,
                'grade_record' => [
                    'id' => $gradeRecord->id,
                    'score' => $gradeRecord->score,
                    'percentage' => $gradeRecord->percentage,
                    'max_score' => $gradeRecord->max_score,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Grade save error: ' . $e->getMessage(), [
                'student_mapping_id' => $request->student_mapping_id,
                'activity_id' => $request->activity_id,
                'score' => $request->score,
                'faculty_id' => $faculty->id ?? null,
            ]);
            
            return response()->json([
                'error' => 'Failed to save grade. Please try again.'
            ], 500);
        }
    }

    /**
     * Update exam score for a term
     */
    public function updateExamScore(Request $request): JsonResponse
    {
        $request->validate([
            'student_mapping_id' => 'required|exists:student_mappings,id',
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'required|in:prelim,midterm,finals',
            'exam_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $studentMapping = StudentMapping::findOrFail($request->student_mapping_id);
        
        // Validate that the student mapping belongs to the subject
        if ($studentMapping->subject_id !== (int)$request->subject_id) {
            return response()->json(['error' => 'Invalid student mapping for this subject'], 400);
        }

        // Find or create term grade record
        $termGrade = TermGrade::updateOrCreate(
            [
                'student_mapping_id' => $request->student_mapping_id,
                'subject_id' => $request->subject_id,
                'term' => $request->term,
            ],
            [
                'exam_score' => $request->exam_score,
            ]
        );

        // Recalculate term grade if we have both class standing and exam score
        if ($termGrade->class_standing !== null && $termGrade->exam_score !== null) {
            $termGrade = $this->calculateTermGrade($termGrade);
        }

        return response()->json([
            'success' => true,
            'term_grade' => [
                'id' => $termGrade->id,
                'exam_score' => $termGrade->exam_score,
                'exam_grade' => $termGrade->exam_grade,
                'term_grade' => $termGrade->term_grade,
            ]
        ]);
    }

    /**
     * Get term grade data for a specific student and term
     */
    public function getTermGrade(Request $request): JsonResponse
    {
        $request->validate([
            'student_mapping_id' => 'required|exists:student_mappings,id',
            'subject_id' => 'required|exists:subjects,id',
            'term' => 'required|in:prelim,midterm,finals',
        ]);

        $termGrade = TermGrade::where('student_mapping_id', $request->student_mapping_id)
            ->where('subject_id', $request->subject_id)
            ->where('term', $request->term)
            ->first();

        if (!$termGrade) {
            return response()->json([
                'class_standing' => null,
                'exam_score' => null,
                'exam_grade' => null,
                'term_grade' => null,
            ]);
        }

        return response()->json([
            'class_standing' => $termGrade->class_standing,
            'exam_score' => $termGrade->exam_score,
            'exam_grade' => $termGrade->exam_grade,
            'term_grade' => $termGrade->term_grade,
        ]);
    }

    /**
     * Calculate term progress for all terms
     */
    private function calculateTermProgress(Subject $subject): array
    {
        $terms = ['prelim', 'midterm', 'finals'];
        $progress = [];

        foreach ($terms as $term) {
            $totalStudents = $subject->studentMappings->count();
            $studentsWithGrades = TermGrade::where('subject_id', $subject->id)
                ->where('term', $term)
                ->whereNotNull('term_grade')
                ->count();

            $progress[$term] = [
                'total_students' => $totalStudents,
                'students_with_grades' => $studentsWithGrades,
                'percentage' => $totalStudents > 0 ? round(($studentsWithGrades / $totalStudents) * 100, 1) : 0,
            ];
        }

        return $progress;
    }

    /**
     * Calculate term grade based on class standing and exam score
     */
    private function calculateTermGrade(TermGrade $termGrade): TermGrade
    {
        $config = config('grading');
        
        if ($termGrade->term === 'prelim') {
            // Prelim: Class standing percentage directly, exam grade = (score/100) * 100
            $examGrade = $termGrade->exam_score;
            $termGradeValue = ($termGrade->class_standing * $config['prelim']['class_weight']) + 
                             ($examGrade * $config['prelim']['exam_weight']);
        } else {
            // Midterm/Finals: Class standing = (score/items) × 50 + 50, exam grade = (score/items) × 50 + 50
            $examGrade = ($termGrade->exam_score / 100) * 50 + 50;
            $termGradeValue = ($termGrade->class_standing * $config['midterm']['class_weight']) + 
                             ($examGrade * $config['midterm']['exam_weight']);
        }

        $termGrade->update([
            'exam_grade' => round($examGrade, 2),
            'term_grade' => round($termGradeValue, 2),
        ]);

        return $termGrade->fresh();
    }

    /**
     * Recalculate class standing for a student in a specific term
     */
    private function recalculateClassStanding(StudentMapping $studentMapping, string $term): void
    {
        // Get all grade records for this student in this term
        $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($studentMapping, $term) {
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

        // Calculate class standing percentage
        $classStandingPercentage = ($totalScore / $totalPossible) * 100;

        // For midterm and finals, apply the transformation: (score/items) × 50 + 50
        if ($term !== 'prelim') {
            $classStanding = ($classStandingPercentage / 100) * 50 + 50;
        } else {
            $classStanding = $classStandingPercentage;
        }

        // Update or create term grade record
        $termGrade = TermGrade::updateOrCreate(
            [
                'student_mapping_id' => $studentMapping->id,
                'subject_id' => $studentMapping->subject_id,
                'term' => $term,
            ],
            [
                'class_standing' => round($classStanding, 2),
            ]
        );

        // Recalculate term grade if we have both class standing and exam score
        if ($termGrade->exam_score !== null) {
            $this->calculateTermGrade($termGrade);
        }
    }

    /**
     * Get grade data for a specific student and activity
     */
    public function getGrade(Request $request): JsonResponse
    {
        $request->validate([
            'student_mapping_id' => 'required|exists:student_mappings,id',
            'activity_id' => 'required|exists:activities,id',
        ]);

        $gradeRecord = GradeRecord::where('student_mapping_id', $request->student_mapping_id)
            ->where('activity_id', $request->activity_id)
            ->first();

        if (!$gradeRecord) {
            return response()->json(['score' => null, 'percentage' => null]);
        }

        return response()->json([
            'score' => $gradeRecord->score,
            'percentage' => $gradeRecord->percentage,
            'max_score' => $gradeRecord->max_score,
        ]);
    }

    /**
     * Import grades from Google Classroom for a specific term
     */
    public function importFromClassroom(Request $request, Subject $subject): JsonResponse
    {
        try {
            $request->validate([
                'term' => 'required|in:prelim,midterm,finals'
            ]);

            $term = $request->term;
            $faculty = Auth::guard('faculty')->user();

            // Check if subject is connected to Google Classroom
            if (!$subject->gcr_class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject is not connected to Google Classroom.'
                ], 422);
            }

            // Authenticate with Google Classroom
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Classroom.'
                ], 401);
            }

            // Get activities for this term
            $activities = Activity::where('subject_id', $subject->id)
                ->where('term', $term)
                ->get();

            if ($activities->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No activities found for this term.'
                ], 422);
            }

            // Get student mappings
            $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();
            if ($studentMappings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students mapped for this subject.'
                ], 422);
            }

            $importedCount = 0;
            $errors = [];

            // Import grades for each activity
            foreach ($activities as $activity) {
                try {
                    // Skip if activity doesn't have a Google Classroom ID
                    if (!$activity->gcr_assignment_id) {
                        $errors[] = "Activity '{$activity->name}' is not connected to Google Classroom";
                        continue;
                    }

                    // Get submissions from Google Classroom
                    $submissions = $this->classroomService->getStudentSubmissions(
                        $subject->gcr_class_id,
                        $activity->gcr_assignment_id
                    );

                    // Process each submission
                    foreach ($submissions as $submission) {
                        // Find the corresponding student mapping
                        $studentMapping = $studentMappings->firstWhere('gcr_student_id', $submission['user_id']);
                        
                        if (!$studentMapping) {
                            continue; // Skip if student not found in mappings
                        }

                        // Get the grade (prefer assigned_grade over draft_grade)
                        $score = $submission['assigned_grade'] ?? $submission['draft_grade'] ?? null;
                        
                        if ($score !== null) {
                            // Create or update grade record
                            GradeRecord::updateOrCreate(
                                [
                                    'student_mapping_id' => $studentMapping->id,
                                    'activity_id' => $activity->id,
                                ],
                                [
                                    'score' => $score,
                                    'max_score' => $activity->max_score,
                                    'percentage' => ($score / $activity->max_score) * 100,
                                ]
                            );

                            $importedCount++;
                        }
                    }

                    // Recalculate class standing for all students after importing grades for this activity
                    foreach ($studentMappings as $studentMapping) {
                        $this->recalculateClassStanding($studentMapping, $term);
                    }

                } catch (\Exception $e) {
                    $errors[] = "Failed to import grades for activity '{$activity->name}': " . $e->getMessage();
                    Log::error('Failed to import grades for activity', [
                        'activity_id' => $activity->id,
                        'activity_name' => $activity->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $message = "Successfully imported {$importedCount} grades from Google Classroom.";
            if (!empty($errors)) {
                $message .= " Some errors occurred: " . implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to import grades from Google Classroom', [
                'subject_id' => $subject->id,
                'term' => $request->term ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import grades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync grades to school database for a specific term
     */
    public function syncToSchoolDatabase(Request $request, Subject $subject): JsonResponse
    {
        try {
            $request->validate([
                'term' => 'required|in:prelim,midterm,finals'
            ]);

            $term = $request->term;

            // Check if subject is mapped to school database
            if (!$subject->school_schedule_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject is not mapped to school database.'
                ], 422);
            }

            // Sync grades for this term
            $results = $this->gradeSyncService->syncSubjectTermGrades($subject, $term);

            if ($results['success'] > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$results['success']} grades to school database.",
                    'results' => $results
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No grades were synced. Make sure students are mapped and grades are finalized.',
                    'results' => $results
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Failed to sync grades to school database', [
                'subject_id' => $subject->id,
                'term' => $request->term ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync grades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync all grades for a subject to school database
     */
    public function syncAllGradesToSchoolDatabase(Subject $subject): JsonResponse
    {
        try {
            // Check if subject is mapped to school database
            if (!$subject->school_schedule_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject is not mapped to school database.'
                ], 422);
            }

            // Sync all grades
            $results = $this->gradeSyncService->syncSubjectGrades($subject);

            if ($results['success'] > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$results['success']} grades to school database.",
                    'results' => $results
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No grades were synced. Make sure students are mapped and grades are finalized.',
                    'results' => $results
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Failed to sync all grades to school database', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync grades: ' . $e->getMessage()
            ], 500);
        }
    }
}
