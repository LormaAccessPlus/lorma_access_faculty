<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Activity;
use App\Models\GradeRecord;
use App\Models\StudentMapping;
use App\Models\TermGrade;
use App\Services\GoogleClassroomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GradeController extends Controller
{
    private GoogleClassroomService $classroomService;

    public function __construct(GoogleClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }
    /**
     * Display the grade matrix for a subject
     */
    public function matrix(Subject $subject): View
    {
        // Check if subject is archived
        if ($subject->isArchived()) {
            return view('grades.matrix-archived', compact('subject'));
        }

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

        // Check if subject is archived
        if ($subject->isArchived()) {
            return view('grades.term-grades-archived', compact('subject', 'term'));
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

        // Get grading configuration for this term
        $gradingConfig = \App\Models\GradingConfig::where('subject_id', $subject->id)
            ->where('term', $term)
            ->first();

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        // Get all term grades for all periods for the calculator
        $allTermGrades = TermGrade::where('subject_id', $subject->id)
            ->with('studentMapping')
            ->get()
            ->groupBy('term');

        // Get all activities for all terms
        $allActivities = Activity::where('subject_id', $subject->id)
            ->orderBy('term')
            ->orderBy('type')
            ->get()
            ->groupBy('term');

        // Get all grade records for all terms
        $allGradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        return view('grades.term-grades', compact(
            'subject', 
            'term', 
            'lectureActivities', 
            'labActivities', 
            'gradeMatrix', 
            'termGrades',
            'termProgress',
            'gradingConfig',
            'finalRatingConfig',
            'allTermGrades',
            'allActivities',
            'allGradeRecords'
        ));
    }

    /**
     * Save grading configuration
     */
    public function saveGradingConfig(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'term' => 'required|in:prelim,midterm,finals',
            'class_standing_weight' => 'required|numeric|min:0|max:100',
            'exam_weight' => 'required|numeric|min:0|max:100',
            'activity_weights' => 'nullable|array',
            'activity_weights.*' => 'nullable|numeric|min:0|max:100',
        ]);

        // Validate weights sum to 100
        if ($request->class_standing_weight + $request->exam_weight != 100) {
            return response()->json([
                'success' => false,
                'message' => 'Class Standing and Exam weights must sum to 100%'
            ], 422);
        }

        // Save grading configuration
        \App\Models\GradingConfig::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'term' => $request->term,
            ],
            [
                'class_standing_weight' => $request->class_standing_weight,
                'exam_weight' => $request->exam_weight,
                'formula_config' => $request->formula_config ?? [],
            ]
        );

        // Save activity weights
        if ($request->has('activity_weights')) {
            foreach ($request->activity_weights as $activityId => $weight) {
                Activity::where('id', $activityId)
                    ->where('subject_id', $subject->id)
                    ->update(['weight' => $weight]);
            }
        }

        // Recalculate all term grades for this term with the new formula
        $this->recalculateTermGradesForTerm($subject, $request->term);

        return response()->json([
            'success' => true,
            'message' => 'Grading configuration saved and grades recalculated successfully'
        ]);
    }

    /**
     * Save final rating configuration
     */
    public function saveFinalRatingConfig(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'prelim_weight' => 'required|numeric|min:0|max:100',
            'midterm_weight' => 'required|numeric|min:0|max:100',
            'finals_weight' => 'required|numeric|min:0|max:100',
        ]);

        // Validate weights sum to 100
        $total = $request->prelim_weight + $request->midterm_weight + $request->finals_weight;
        if ($total != 100) {
            return response()->json([
                'success' => false,
                'message' => 'Term weights must sum to 100%'
            ], 422);
        }

        $subject->update([
            'final_rating_config' => [
                'prelim_weight' => $request->prelim_weight,
                'midterm_weight' => $request->midterm_weight,
                'finals_weight' => $request->finals_weight,
            ]
        ]);

        // Recalculate final ratings for all students with the new weights
        $this->recalculateFinalRatings($subject);

        return response()->json([
            'success' => true,
            'message' => 'Final rating configuration saved and final ratings recalculated successfully'
        ]);
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
            'exam_score' => 'nullable|numeric|min:0',
            'exam_max_score' => 'nullable|numeric|min:1',
        ]);

        $studentMapping = StudentMapping::findOrFail($request->student_mapping_id);
        
        // Validate that the student mapping belongs to the subject
        if ($studentMapping->subject_id !== (int)$request->subject_id) {
            return response()->json(['error' => 'Invalid student mapping for this subject'], 400);
        }

        // Validate score doesn't exceed max score
        if ($request->exam_score !== null && $request->exam_max_score !== null && $request->exam_score > $request->exam_max_score) {
            return response()->json(['error' => 'Exam score cannot exceed maximum score'], 400);
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
                'exam_max_score' => $request->exam_max_score ?? 100,
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
                'exam_max_score' => $termGrade->exam_max_score,
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
        // Get grading configuration from database for this subject and term
        $gradingConfig = \App\Models\GradingConfig::where('subject_id', $termGrade->subject_id)
            ->where('term', $termGrade->term)
            ->first();
        
        // Fallback to config file if no database config exists
        if (!$gradingConfig) {
            $config = config('grading');
            $classWeight = $config[$termGrade->term]['class_weight'];
            $examWeight = $config[$termGrade->term]['exam_weight'];
        } else {
            $classWeight = $gradingConfig->class_standing_weight / 100;
            $examWeight = $gradingConfig->exam_weight / 100;
        }
        
        // Calculate exam percentage: (score / max_score) × 100
        $examMaxScore = $termGrade->exam_max_score ?? 100;
        $examPercentage = ($termGrade->exam_score / $examMaxScore) * 100;
        
        // Calculate exam grade as weighted percentage
        // Exam Grade = Percentage × Exam Weight %
        // Example: 60% × 60% = 36
        $examGrade = $examPercentage * ($examWeight);
        
        // Calculate term grade
        $termGradeValue = $termGrade->class_standing + $examGrade;

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

        // Get grading configuration to get the activity weight
        $gradingConfig = \App\Models\GradingConfig::where('subject_id', $studentMapping->subject_id)
            ->where('term', $term)
            ->first();
        
        // Get activity weight (default to 40% if not configured)
        $activityWeight = $gradingConfig ? $gradingConfig->class_standing_weight : 40;
        
        // Calculate class standing percentage
        $classStandingPercentage = ($totalScore / $totalPossible) * 100;

        // Apply the activity weight to get the class standing
        // Class Standing = Percentage × Activity Weight %
        // Example: 75.3% × 40% = 30.13
        $classStanding = $classStandingPercentage * ($activityWeight / 100);

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
            $skippedActivities = [];

            // Import grades for each activity
            foreach ($activities as $activity) {
                try {
                    // Skip if activity doesn't have a Google Classroom ID
                    if (!$activity->gcr_assignment_id) {
                        $skippedActivities[] = $activity->name;
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
                            Log::info('Student not found in mappings', [
                                'gcr_student_id' => $submission['user_id'],
                                'activity' => $activity->name
                            ]);
                            continue; // Skip if student not found in mappings
                        }

                        // Get the grade (prefer assigned_grade over draft_grade)
                        $score = $submission['assigned_grade'] ?? $submission['draft_grade'] ?? null;
                        
                        Log::info('Processing submission', [
                            'student' => $studentMapping->student_name,
                            'activity' => $activity->name,
                            'draft_grade' => $submission['draft_grade'] ?? 'null',
                            'assigned_grade' => $submission['assigned_grade'] ?? 'null',
                            'final_score' => $score,
                            'state' => $submission['state'] ?? 'unknown'
                        ]);
                        
                        if ($score !== null) {
                            // Create or update grade record
                            $gradeRecord = GradeRecord::updateOrCreate(
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

                            Log::info('Grade record saved', [
                                'grade_record_id' => $gradeRecord->id,
                                'score' => $gradeRecord->score,
                                'percentage' => $gradeRecord->percentage
                            ]);

                            $importedCount++;
                        } else {
                            Log::info('No grade to import', [
                                'student' => $studentMapping->student_name,
                                'activity' => $activity->name
                            ]);
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

            // Build success message
            $message = "Successfully imported {$importedCount} grade(s) from Google Classroom.";
            
            // Add info about skipped web-only activities
            if (!empty($skippedActivities)) {
                $skippedCount = count($skippedActivities);
                $message .= " {$skippedCount} web-only activity(ies) skipped (manual entry required).";
            }
            
            // Add actual errors if any
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => count($skippedActivities),
                'skipped_activities' => $skippedActivities,
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
     * Export Activities + Exam scores to PDF
     */
    public function exportActivities(Subject $subject)
    {
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        $activitiesByTerm = $subject->activities->groupBy('term');
        
        $gradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        $termGrades = TermGrade::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with('studentMapping')->get();

        $pdf = \PDF::loadView('grades.exports.activities', compact('subject', 'activitiesByTerm', 'gradeRecords', 'termGrades'));
        
        return $pdf->download($subject->subject_code . '_Activities_Exam.pdf');
    }

    /**
     * Export Grade (PP) - Computed grades in percentage
     */
    public function exportPP(Subject $subject)
    {
        $subject->load([
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        $termGrades = TermGrade::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with('studentMapping')->get();

        $pdf = \PDF::loadView('grades.exports.pp', compact('subject', 'termGrades'));
        
        return $pdf->download($subject->subject_code . '_Grades_PP.pdf');
    }

    /**
     * Export Term-Based Grading to PDF
     */
    public function exportTerm(Subject $subject, string $term)
    {
        $subject->load([
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        $termGrades = TermGrade::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })
        ->where('term', $term)
        ->with('studentMapping')
        ->get();

        $pdf = \PDF::loadView('grades.exports.term', compact('subject', 'term', 'termGrades'));
        
        return $pdf->download($subject->subject_code . '_' . ucfirst($term) . '_Grades.pdf');
    }

    /**
     * Recalculate all term grades for a specific term with new formula weights
     */
    private function recalculateTermGradesForTerm(Subject $subject, string $term): void
    {
        // Get grading configuration for this term
        $gradingConfig = \App\Models\GradingConfig::where('subject_id', $subject->id)
            ->where('term', $term)
            ->first();

        if (!$gradingConfig) {
            return; // No config, nothing to recalculate
        }

        $classStandingWeight = $gradingConfig->class_standing_weight;
        $examWeight = $gradingConfig->exam_weight;

        // Get all student mappings for this subject
        $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();

        foreach ($studentMappings as $studentMapping) {
            // Recalculate class standing for this student and term
            $this->recalculateClassStanding($studentMapping, $term);

            // Get the term grade record
            $termGrade = TermGrade::where('student_mapping_id', $studentMapping->id)
                ->where('subject_id', $subject->id)
                ->where('term', $term)
                ->first();

            // Recalculate term grade if we have both class standing and exam score
            if ($termGrade && $termGrade->class_standing !== null && $termGrade->exam_score !== null) {
                $classWeight = $classStandingWeight / 100;
                $examWeightDecimal = $examWeight / 100;

                // Calculate exam percentage: (score / max_score) × 100
                $examMaxScore = $termGrade->exam_max_score ?? 100;
                $examPercentage = ($termGrade->exam_score / $examMaxScore) * 100;

                // Calculate exam grade as weighted percentage
                // Exam Grade = Percentage × Exam Weight %
                $examGrade = $examPercentage * $examWeightDecimal;

                // Calculate term grade (class standing + exam grade)
                $termGradeValue = $termGrade->class_standing + $examGrade;

                $termGrade->update([
                    'exam_grade' => round($examGrade, 2),
                    'term_grade' => round($termGradeValue, 2),
                ]);
            }
        }
    }

    /**
     * Recalculate final ratings for all students with new term weights
     */
    private function recalculateFinalRatings(Subject $subject): void
    {
        // Get the final rating config from the subject
        $config = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        $prelimWeight = $config['prelim_weight'];
        $midtermWeight = $config['midterm_weight'];
        $finalsWeight = $config['finals_weight'];

        // Get all student mappings for this subject
        $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();

        foreach ($studentMappings as $studentMapping) {
            // Get term grades for this student
            $prelimGrade = TermGrade::where('student_mapping_id', $studentMapping->id)
                ->where('subject_id', $subject->id)
                ->where('term', 'prelim')
                ->first();

            $midtermGrade = TermGrade::where('student_mapping_id', $studentMapping->id)
                ->where('subject_id', $subject->id)
                ->where('term', 'midterm')
                ->first();

            $finalsGrade = TermGrade::where('student_mapping_id', $studentMapping->id)
                ->where('subject_id', $subject->id)
                ->where('term', 'finals')
                ->first();

            // Only calculate if all term grades exist
            if ($prelimGrade && $prelimGrade->term_grade !== null &&
                $midtermGrade && $midtermGrade->term_grade !== null &&
                $finalsGrade && $finalsGrade->term_grade !== null) {
                
                $finalRating = ($prelimGrade->term_grade * ($prelimWeight / 100)) +
                              ($midtermGrade->term_grade * ($midtermWeight / 100)) +
                              ($finalsGrade->term_grade * ($finalsWeight / 100));

                // Update final rating in one of the term grades (or create a separate final rating record)
                // For now, we'll store it in the finals term grade
                $finalsGrade->update([
                    'final_rating' => round($finalRating, 2)
                ]);
            }
        }
    }
}
