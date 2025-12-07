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
use Barryvdh\DomPDF\Facade\Pdf as PDF;

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
                $query->whereNotNull('student_id')->orderBy('student_name');
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

        // Recalculate all term grades to ensure exam_grade is always correct
        // This ensures that when the page loads, all calculations follow the current formula
        $termGrades = TermGrade::where('subject_id', $subject->id)->get();
        foreach ($termGrades as $termGrade) {
            // Only recalculate if there's an exam score
            if ($termGrade->exam_score !== null) {
                $this->calculateTermGrade($termGrade, $subject->matrix_type);
            }
        }

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        return view('grades.matrix', compact('subject', 'activitiesByTerm', 'gradeMatrix', 'finalRatingConfig'));
    }

    /**
     * Display the full grade matrix for a subject (all terms with all activities)
     */
    public function fullMatrix(Subject $subject): View
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

        // Get all activities grouped by term
        $allActivities = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // Get all term grades grouped by student
        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        return view('grades.full-matrix', compact(
            'subject',
            'allActivities',
            'gradeMatrix',
            'termGrades',
            'finalRatingConfig'
        ));
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
                $query->whereNotNull('student_id')->orderBy('student_name');
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

        // Auto-calculate class standing for students who have grades but no class standing
        foreach ($subject->studentMappings as $studentMapping) {
            $hasGrades = isset($gradeMatrix[$studentMapping->id]) && !empty($gradeMatrix[$studentMapping->id]);
            $hasClassStanding = isset($termGrades[$studentMapping->id]) && $termGrades[$studentMapping->id]->class_standing !== null;
            
            if ($hasGrades && !$hasClassStanding) {
                $this->recalculateClassStanding($studentMapping, $term);
            }
        }
        
        // Reload term grades after auto-calculation
        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->where('term', $term)
            ->get()
            ->keyBy('student_mapping_id');

        // Recalculate all term grades for this term to ensure exam_grade is always correct
        // This ensures that when the page loads, all calculations follow the current formula
        foreach ($termGrades as $termGrade) {
            // Only recalculate if there's an exam score
            if ($termGrade->exam_score !== null) {
                $this->calculateTermGrade($termGrade, $subject->matrix_type);
            }
        }

        // Reload term grades after recalculation
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
            'subject_type' => 'nullable|string|in:lecture,lab',
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

        $newConfig = [
            'subject_type' => $request->subject_type ?? 'lecture',
            'prelim_weight' => $request->prelim_weight,
            'midterm_weight' => $request->midterm_weight,
            'finals_weight' => $request->finals_weight,
        ];

        Log::info('Saving final rating config', [
            'subject_id' => $subject->id,
            'old_config' => $subject->final_rating_config,
            'new_config' => $newConfig
        ]);

        $subject->update([
            'final_rating_config' => $newConfig
        ]);

        // Refresh the subject to get the updated config
        $subject->refresh();

        Log::info('After save and refresh', [
            'subject_id' => $subject->id,
            'config_from_db' => $subject->final_rating_config
        ]);

        // Recalculate final ratings for all students with the new weights
        $this->recalculateFinalRatings($subject);

        return response()->json([
            'success' => true,
            'message' => 'Final rating configuration saved and final ratings recalculated successfully',
            'config' => $subject->final_rating_config
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

            // Get matrix type from request (if provided)
            $matrixType = $request->input('matrix_type', null);
            
            // Recalculate class standing for this student and term
            $this->recalculateClassStanding($studentMapping, $activity->term, $matrixType);

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

        // Get matrix type from request (from the page/route), NOT from database
        $subject = Subject::find($request->subject_id);
        $matrixType = $request->input('matrix_type', null);
        
        // If no matrix type provided in request, fall back to subject's matrix_type
        if (!$matrixType) {
            $matrixType = $subject->matrix_type;
        }
        
        $isNursing = $matrixType === 'nursing';

        if ($isNursing) {
            // For nursing, recalculate the entire term grade with activities and quizzes
            $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($studentMapping, $request) {
                $query->where('subject_id', $studentMapping->subject_id)
                      ->where('term', $request->term);
            })->where('student_mapping_id', $studentMapping->id)->get();

            // Get all activities for this term
            $activities = Activity::where('subject_id', $studentMapping->subject_id)
                ->where('term', $request->term)
                ->get();

            // Separate activities and quizzes
            $activityRecords = collect();
            $quizRecords = collect();
            
            foreach ($gradeRecords as $gradeRecord) {
                $activity = $activities->firstWhere('id', $gradeRecord->activity_id);
                if ($activity) {
                    if ($activity->activity_category === 'quiz') {
                        $quizRecords->push($gradeRecord);
                    } else {
                        $activityRecords->push($gradeRecord);
                    }
                }
            }

            // Calculate totals
            // Sum scores from grade records, but max scores from ALL activities (not just graded ones)
            $activitiesTotal = 0;
            $activitiesMax = 0;
            
            foreach ($activities->where('activity_category', '!=', 'quiz') as $activity) {
                $gradeRecord = $gradeRecords->firstWhere('activity_id', $activity->id);
                if ($gradeRecord && $gradeRecord->score !== null) {
                    $activitiesTotal += floatval($gradeRecord->score);
                }
                // Always add max score, even if no grade record exists
                $activitiesMax += floatval($activity->max_score);
            }
            
            // Calculate totals for quizzes
            $quizzesTotal = 0;
            $quizzesMax = 0;
            
            foreach ($activities->where('activity_category', 'quiz') as $quiz) {
                $gradeRecord = $gradeRecords->firstWhere('activity_id', $quiz->id);
                if ($gradeRecord && $gradeRecord->score !== null) {
                    $quizzesTotal += floatval($gradeRecord->score);
                }
                // Always add max score, even if no grade record exists
                $quizzesMax += floatval($quiz->max_score);
            }

            // Use nursing calculator
            $calculator = new \App\Services\NursingGradeCalculator();
            
            $activitiesScore = $calculator->calculateActivityScore($activitiesTotal, $activitiesMax);
            $quizzesScore = $calculator->calculateQuizScore($quizzesTotal, $quizzesMax);
            $examGrade = $calculator->calculateExamScore($request->exam_score ?? 0, $request->exam_max_score ?? 100);
            $termGradeValue = $calculator->calculateTermGrade($activitiesScore, $quizzesScore, $examGrade);

            // Update or create term grade record
            $termGrade = TermGrade::updateOrCreate(
                [
                    'student_mapping_id' => $request->student_mapping_id,
                    'subject_id' => $request->subject_id,
                    'term' => $request->term,
                ],
                [
                    'exam_score' => $request->exam_score,
                    'exam_max_score' => $request->exam_max_score ?? 100,
                    'exam_grade' => round($examGrade, 2),
                    'term_grade' => round($termGradeValue, 2),
                    'computation_config' => [
                        'activities_total' => $activitiesTotal,
                        'activities_max' => $activitiesMax,
                        'activities_score' => round($activitiesScore, 2),
                        'quizzes_total' => $quizzesTotal,
                        'quizzes_max' => $quizzesMax,
                        'quizzes_score' => round($quizzesScore, 2),
                        'exam_total' => $request->exam_score ?? 0,
                        'exam_max' => $request->exam_max_score ?? 100,
                    ],
                ]
            );
        } else {
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

            // Always recalculate term grade when exam score is updated
            // This ensures exam_grade is always calculated with the correct formula
            // Use the matrix_type from the request (page/route), not from database
            $termGrade = $this->calculateTermGrade($termGrade, $matrixType);
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
                'activities_score' => null,
                'quizzes_score' => null,
            ]);
        }

        // Extract nursing-specific scores from computation_config if available
        $computationConfig = $termGrade->computation_config ?? [];
        
        return response()->json([
            'class_standing' => $termGrade->class_standing,
            'exam_score' => $termGrade->exam_score,
            'exam_grade' => $termGrade->exam_grade,
            'term_grade' => $termGrade->term_grade,
            'activities_score' => $computationConfig['activities_score'] ?? null,
            'quizzes_score' => $computationConfig['quizzes_score'] ?? null,
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
    private function calculateTermGrade(TermGrade $termGrade, ?string $matrixType = null): TermGrade
    {
        // Matrix type should be provided from the page/route
        // If not provided, we cannot calculate correctly
        if (!$matrixType) {
            // Fallback: try to get from subject (for backward compatibility)
            $subject = Subject::find($termGrade->subject_id);
            if ($subject && $subject->matrix_type) {
                $matrixType = $subject->matrix_type;
            } else {
                // No matrix type available - cannot calculate
                return $termGrade;
            }
        }

        // Check if this is a nursing subject
        $isNursing = $matrixType === 'nursing';

        if ($isNursing) {
            // For nursing, use the nursing-specific calculation
            // But we still need to calculate it here for exam score updates
            // Only skip if class_standing is null (no activities graded yet)
            if ($termGrade->class_standing === null) {
                return $termGrade->fresh();
            }
        }

        // ALWAYS use the matrix type formula if available
        // This ensures consistency with the matrix type's defined weights
        if ($matrixType) {
            $formulaConfig = \App\Services\MatrixFormulaService::getFormulaConfig($matrixType, $termGrade->term);
            $classWeight = $formulaConfig['class_standing_weight'] / 100;
            $examWeight = $formulaConfig['exam_weight'] / 100;
            $examFormulaType = $formulaConfig['exam_formula'];
        } else {
            // Only fall back to database config if no matrix type is available
            // This should rarely happen
            $gradingConfig = \App\Models\GradingConfig::where('subject_id', $termGrade->subject_id)
                ->where('term', $termGrade->term)
                ->first();
            
            // Fallback to config file if no database config exists
            if (!$gradingConfig) {
                $config = config('grading');
                $classWeight = $config[$termGrade->term]['class_weight'];
                $examWeight = $config[$termGrade->term]['exam_weight'];
                $examFormulaType = 'percentage';
            } else {
                $classWeight = $gradingConfig->class_standing_weight / 100;
                $examWeight = $gradingConfig->exam_weight / 100;
                $examFormulaType = $gradingConfig->formula_config['type'] ?? 'percentage';
            }
        }
        
        // Calculate raw exam score using the appropriate formula
        $rawExamScore = 0;
        if ($termGrade->exam_score !== null) {
            $examMaxScore = $termGrade->exam_max_score ?? 100;
            
            // Calculate raw exam score using the formula service
            // For general education: (score/max) × 50 + 50 (transmuted)
            // Example: (22/30) × 50 + 50 = 86.67
            $rawExamScore = \App\Services\MatrixFormulaService::calculateExamScore(
                $termGrade->exam_score,
                $examMaxScore,
                $examFormulaType
            );
        }
        
        // Apply weight to exam score to get weighted exam grade contribution
        // For general education: 86.67 × 33.33% = 28.89
        // For zero-based: 90 × 60% = 54
        $weightedExamGrade = $rawExamScore * $examWeight;
        
        // Calculate term grade by adding weighted class standing and weighted exam grade
        // Note: class_standing is already weighted (stored as contribution to term grade)
        // Example: 63.45 (weighted CS) + 28.89 (weighted exam) = 92.34
        $termGradeValue = ($termGrade->class_standing ?? 0) + $weightedExamGrade;

        // Store the WEIGHTED exam grade (after applying weight) in exam_grade for display
        // This shows the weighted contribution to the term grade
        // Example: For zero-based with 90/100 exam: (90/100)*100 = 90, then 90 × 60% = 54 (displayed as Exam Grade)
        $termGrade->update([
            'exam_grade' => $termGrade->exam_score !== null ? round($weightedExamGrade, 2) : null,
            'term_grade' => round($termGradeValue, 2),
        ]);

        return $termGrade->fresh();
    }

    /**
     * Recalculate class standing for a student in a specific term
     */
    private function recalculateClassStanding(StudentMapping $studentMapping, string $term, ?string $matrixType = null): void
    {
        // Get all grade records for this student in this term
        $gradeRecords = GradeRecord::whereHas('activity', function ($query) use ($studentMapping, $term) {
            $query->where('subject_id', $studentMapping->subject_id)
                  ->where('term', $term);
        })->where('student_mapping_id', $studentMapping->id)->get();

        Log::info('Recalculating class standing', [
            'student_mapping_id' => $studentMapping->id,
            'term' => $term,
            'matrix_type' => $matrixType,
            'grade_records_count' => $gradeRecords->count()
        ]);

        if ($gradeRecords->isEmpty()) {
            Log::info('No grade records found for student in term', [
                'student_mapping_id' => $studentMapping->id,
                'term' => $term
            ]);
            return;
        }

        // Check if this is a nursing subject
        $subject = Subject::find($studentMapping->subject_id);
        $isNursing = $matrixType === 'nursing';

        Log::info('Checking if nursing', [
            'matrix_type_param' => $matrixType,
            'subject_matrix_type' => $subject?->matrix_type,
            'is_nursing' => $isNursing
        ]);

        if ($isNursing) {
            // Use nursing-specific calculation
            Log::info('Using nursing calculation');
            $this->recalculateNursingTermGrade($studentMapping, $term, $gradeRecords);
            return;
        }

        // Calculate total score and total possible score
        $totalScore = $gradeRecords->sum('score');
        $totalPossible = $gradeRecords->sum('max_score');

        Log::info('Class standing calculation', [
            'student_mapping_id' => $studentMapping->id,
            'term' => $term,
            'total_score' => $totalScore,
            'total_possible' => $totalPossible
        ]);

        if ($totalPossible == 0) {
            Log::warning('Total possible score is 0', [
                'student_mapping_id' => $studentMapping->id,
                'term' => $term
            ]);
            return;
        }

        // If matrix type is provided, use it to determine formula
        // Otherwise, fall back to database config
        if ($matrixType) {
            $formulaConfig = \App\Services\MatrixFormulaService::getFormulaConfig($matrixType, $term);
            $formulaType = $formulaConfig['cs_formula'];
            $csWeight = $formulaConfig['class_standing_weight'];
        } else {
            // Get grading configuration from database
            $gradingConfig = \App\Models\GradingConfig::where('subject_id', $studentMapping->subject_id)
                ->where('term', $term)
                ->first();
            
            // Get formula type from config (default to 'percentage')
            $formulaType = 'percentage';
            $csWeight = 40; // default weight
            if ($gradingConfig) {
                if (isset($gradingConfig->formula_config['type'])) {
                    $formulaType = $gradingConfig->formula_config['type'];
                }
                $csWeight = $gradingConfig->class_standing_weight;
            }
        }
        
        // Calculate raw class standing score using the formula service
        $rawClassStanding = \App\Services\MatrixFormulaService::calculateClassStanding(
            $totalScore,
            $totalPossible,
            $formulaType
        );
        
        // Apply weight to get the weighted class standing (contribution to term grade)
        // Example: 95.17 × 66.67% = 63.45
        $classStanding = $rawClassStanding * ($csWeight / 100);

        Log::info('Class standing calculated', [
            'student_mapping_id' => $studentMapping->id,
            'term' => $term,
            'raw_class_standing' => $rawClassStanding,
            'cs_weight' => $csWeight,
            'weighted_class_standing' => $classStanding,
            'formula_type' => $formulaType,
            'matrix_type' => $matrixType
        ]);

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

        // Always recalculate term grade (will handle null exam scores)
        $this->calculateTermGrade($termGrade, $matrixType);
    }

    /**
     * Recalculate nursing term grade with activities and quizzes separated
     */
    private function recalculateNursingTermGrade(StudentMapping $studentMapping, string $term, $gradeRecords): void
    {
        // Get all activities for this term
        $activities = Activity::where('subject_id', $studentMapping->subject_id)
            ->where('term', $term)
            ->get();

        // Separate activities and quizzes
        $activityRecords = collect();
        $quizRecords = collect();
        
        foreach ($gradeRecords as $gradeRecord) {
            $activity = $activities->firstWhere('id', $gradeRecord->activity_id);
            if ($activity) {
                if ($activity->activity_category === 'quiz') {
                    $quizRecords->push($gradeRecord);
                } else {
                    $activityRecords->push($gradeRecord);
                }
            }
        }

        // Calculate totals for activities
        // Sum scores from grade records, but max scores from ALL activities (not just graded ones)
        $activitiesTotal = 0;
        $activitiesMax = 0;
        
        foreach ($activities->where('activity_category', '!=', 'quiz') as $activity) {
            $gradeRecord = $gradeRecords->firstWhere('activity_id', $activity->id);
            if ($gradeRecord && $gradeRecord->score !== null) {
                $activitiesTotal += floatval($gradeRecord->score);
            }
            // Always add max score, even if no grade record exists
            $activitiesMax += floatval($activity->max_score);
        }

        // Calculate totals for quizzes
        // Sum scores from grade records, but max scores from ALL quizzes (not just graded ones)
        $quizzesTotal = 0;
        $quizzesMax = 0;
        
        foreach ($activities->where('activity_category', 'quiz') as $quiz) {
            $gradeRecord = $gradeRecords->firstWhere('activity_id', $quiz->id);
            if ($gradeRecord && $gradeRecord->score !== null) {
                $quizzesTotal += floatval($gradeRecord->score);
            }
            // Always add max score, even if no grade record exists
            $quizzesMax += floatval($quiz->max_score);
        }

        // Use nursing calculator
        $calculator = new \App\Services\NursingGradeCalculator();
        
        $activitiesScore = $calculator->calculateActivityScore($activitiesTotal, $activitiesMax);
        $quizzesScore = $calculator->calculateQuizScore($quizzesTotal, $quizzesMax);

        // Get existing term grade to preserve exam score
        $existingTermGrade = TermGrade::where('student_mapping_id', $studentMapping->id)
            ->where('subject_id', $studentMapping->subject_id)
            ->where('term', $term)
            ->first();

        $examScore = $existingTermGrade?->exam_score ?? 0;
        $examMaxScore = $existingTermGrade?->exam_max_score ?? 100;
        
        $examGrade = $calculator->calculateExamScore($examScore, $examMaxScore);
        $termGrade = $calculator->calculateTermGrade($activitiesScore, $quizzesScore, $examGrade);

        // Update or create term grade record with computation_config
        TermGrade::updateOrCreate(
            [
                'student_mapping_id' => $studentMapping->id,
                'subject_id' => $studentMapping->subject_id,
                'term' => $term,
            ],
            [
                'exam_score' => $examScore,
                'exam_max_score' => $examMaxScore,
                'exam_grade' => round($examGrade, 2),
                'term_grade' => round($termGrade, 2),
                'computation_config' => [
                    'activities_total' => $activitiesTotal,
                    'activities_max' => $activitiesMax,
                    'activities_score' => round($activitiesScore, 2),
                    'quizzes_total' => $quizzesTotal,
                    'quizzes_max' => $quizzesMax,
                    'quizzes_score' => round($quizzesScore, 2),
                    'exam_total' => $examScore,
                    'exam_max' => $examMaxScore,
                ],
            ]
        );

        Log::info('Nursing term grade calculated', [
            'student_mapping_id' => $studentMapping->id,
            'term' => $term,
            'activities_total' => $activitiesTotal,
            'activities_max' => $activitiesMax,
            'activities_score' => $activitiesScore,
            'quizzes_total' => $quizzesTotal,
            'quizzes_max' => $quizzesMax,
            'quizzes_score' => $quizzesScore,
            'exam_grade' => $examGrade,
            'term_grade' => $termGrade,
        ]);
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
    public function exportActivities(Request $request, Subject $subject)
    {
        try {
            $subject->load([
                'activities' => function ($query) {
                    $query->orderBy('term')->orderBy('type')->orderBy('created_at');
                },
                'studentMappings' => function ($query) {
                    $query->whereNotNull('student_id')->orderBy('student_name');
                }
            ]);

            $activitiesByTerm = $subject->activities->groupBy('term');
            
            $gradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
                $query->where('subject_id', $subject->id);
            })->with(['studentMapping', 'activity'])->get();

            $termGrades = TermGrade::whereHas('studentMapping', function ($query) use ($subject) {
                $query->where('subject_id', $subject->id);
            })->with('studentMapping')->get();

            // Get dean name from request and faculty from auth
            $deanName = $request->input('dean_name', '');
            $faculty = Auth::guard('faculty')->user();
            $adviserName = $faculty ? $faculty->name : '';

            $pdf = \PDF::loadView('grades.exports.activities', compact('subject', 'activitiesByTerm', 'gradeRecords', 'termGrades', 'deanName', 'adviserName'));
            
            return $pdf->download($subject->subject_code . '_Activities_Exam.pdf');
        } catch (\Exception $e) {
            Log::error('Failed to export activities PDF', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export Grade (PP) - Computed grades in percentage
     */
    public function exportPP(Request $request, Subject $subject)
    {
        try {
            // Refresh subject to get latest data from database
            $subject->refresh();
            
            $subject->load([
                'studentMappings' => function ($query) {
                    $query->whereNotNull('student_id')->orderBy('student_name');
                }
            ]);

            $termGrades = TermGrade::whereHas('studentMapping', function ($query) use ($subject) {
                $query->where('subject_id', $subject->id);
            })->with('studentMapping')->get();

            // Get dean name from request
            $deanName = $request->input('dean_name', '');
            
            $faculty = Auth::guard('faculty')->user();
            $adviserName = $faculty ? $faculty->name : '';

            // Get matrix type from request (for dynamic matrix type) or use subject's stored type
            $matrixType = $request->input('matrix_type', $subject->matrix_type);
            
            // Log the config for debugging
            Log::info('Exporting PP PDF', [
                'subject_id' => $subject->id,
                'matrix_type_from_request' => $request->input('matrix_type'),
                'subject_matrix_type' => $subject->matrix_type,
                'using_matrix_type' => $matrixType,
                'final_rating_config' => $subject->final_rating_config
            ]);

            // Pass matrix type to the view
            $pdf = \PDF::loadView('grades.exports.pp', compact('subject', 'termGrades', 'deanName', 'adviserName', 'matrixType'));
            
            return $pdf->download($subject->subject_code . '_Grades_PP.pdf');
        } catch (\Exception $e) {
            Log::error('Failed to export PP PDF', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export Full Grade Matrix to PDF
     */
    public function exportFullMatrix(Request $request, Subject $subject)
    {
        try {
            $subject->load([
                'activities' => function ($query) {
                    $query->orderBy('term')->orderBy('type')->orderBy('created_at');
                },
                'studentMappings' => function ($query) {
                    $query->orderBy('student_name');
                }
            ]);

            // Get all activities grouped by term
            $allActivities = $subject->activities->groupBy('term');
            
            // Get existing grade records for this subject
            $gradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
                $query->where('subject_id', $subject->id);
            })->with(['studentMapping', 'activity'])->get();

            // Create a matrix structure for easy access
            $gradeMatrix = [];
            foreach ($gradeRecords as $record) {
                $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
            }

            // Get all term grades grouped by student
            $termGrades = TermGrade::where('subject_id', $subject->id)
                ->get()
                ->groupBy('student_mapping_id');

            // Get final rating configuration
            $finalRatingConfig = $subject->final_rating_config ?? [
                'prelim_weight' => 30,
                'midterm_weight' => 30,
                'finals_weight' => 40
            ];

            // Get dean name from request and faculty from auth
            $deanName = $request->input('dean_name', '');
            $faculty = Auth::guard('faculty')->user();
            $adviserName = $faculty ? $faculty->name : '';

            $pdf = \PDF::loadView('grades.exports.full-matrix', compact(
                'subject',
                'allActivities',
                'gradeMatrix',
                'termGrades',
                'finalRatingConfig',
                'deanName',
                'adviserName'
            ));
            
            // Set landscape orientation for full matrix
            $pdf->setPaper('legal', 'landscape');
            
            return $pdf->download($subject->subject_code . '_Full_Grade_Matrix.pdf');
        } catch (\Exception $e) {
            Log::error('Failed to export Full Matrix PDF', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export Term-Based Grading to PDF
     */
    public function exportTerm(Request $request, Subject $subject, string $term)
    {
        $subject->load([
            'studentMappings' => function ($query) {
                $query->whereNotNull('student_id')->orderBy('student_name');
            }
        ]);

        $termGrades = TermGrade::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })
        ->where('term', $term)
        ->with('studentMapping')
        ->get();

        // Get dean name from request and faculty from auth
        $deanName = $request->input('dean_name', '');
        $faculty = Auth::guard('faculty')->user();
        $adviserName = $faculty ? $faculty->name : '';

        $pdf = \PDF::loadView('grades.exports.term', compact('subject', 'term', 'termGrades', 'deanName', 'adviserName'));
        
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

            // Recalculate term grade using the calculateTermGrade method
            // This ensures the correct formula (percentage or transmuted) is applied
            if ($termGrade) {
                $this->calculateTermGrade($termGrade, $subject->matrix_type);
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
