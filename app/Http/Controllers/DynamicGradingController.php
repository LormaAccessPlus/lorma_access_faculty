<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradingClass;
use App\Models\GradingComponent;
use App\Models\ComponentItem;
use App\Models\StudentGrade;
use App\Models\StudentMapping;
use App\Models\Activity;
use App\Models\MatrixComponent;
use App\Models\MatrixComponentScore;
use App\Services\GoogleClassroomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DynamicGradingController extends Controller
{
    public function index(Request $request)
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Auto-check and archive subjects if their GCR classrooms are archived
        $this->autoCheckArchivedSubjects($faculty);
        
        // Get grading classes for this faculty (only for active subjects)
        $gradingClasses = GradingClass::where('faculty_id', $faculty->id)
            ->whereHas('subject', function($query) {
                $query->whereNull('archived_at');
            })
            ->with(['subject', 'components.items'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available subjects (not yet added as grading classes)
        $availableSubjects = Subject::where('faculty_id', $faculty->id)
            ->active()
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->whereNotIn('id', $gradingClasses->pluck('subject_id'))
            ->get();

        return view('grading.index', compact('gradingClasses', 'availableSubjects'));
    }

    public function addClass(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'department' => 'required|string',
        ]);

        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        $subject = Subject::where('id', $request->subject_id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        // Check if class already exists for any term
        $existing = GradingClass::where('subject_id', $subject->id)->first();

        if ($existing) {
            return back()->with('error', 'This class has already been added to grading.');
        }

        // Create grading classes for all three terms
        $terms = ['prelim', 'midterm', 'finals'];
        $createdClasses = [];

        foreach ($terms as $term) {
            $gradingClass = GradingClass::create([
                'subject_id' => $subject->id,
                'faculty_id' => $faculty->id,
                'gcr_class_id' => $subject->gcr_class_id,
                'class_name' => $subject->subject_name . ' - ' . $subject->section,
                'department' => $request->department,
                'term' => $term,
                'term_formula' => null, // Will be set during configuration
            ]);
            
            $createdClasses[] = $gradingClass;
        }

        // Redirect to the grade sheet for prelim term
        return redirect()->route('grading.grade-sheet', $createdClasses[0]->id)
            ->with('success', 'Class added successfully for all terms (Prelim, Midterm, Finals). Configure your grading components.');
    }

    public function show($subjectId)
    {
        $faculty = auth('faculty')->user();
        
        // Get all grading classes for this subject
        $gradingClasses = GradingClass::where('subject_id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->with(['subject', 'components.items'])
            ->get();

        if ($gradingClasses->isEmpty()) {
            return redirect()->route('grading.index')->with('error', 'No grading classes found for this subject.');
        }

        $subject = $gradingClasses->first()->subject;
        
        // Organize by term
        $prelim = $gradingClasses->where('term', 'prelim')->first();
        $midterm = $gradingClasses->where('term', 'midterm')->first();
        $finals = $gradingClasses->where('term', 'finals')->first();

        return view('grading.show', compact('subject', 'prelim', 'midterm', 'finals'));
    }

    public function configure($id)
    {
        $faculty = auth('faculty')->user();
        $gradingClass = GradingClass::where('id', $id)
            ->where('faculty_id', $faculty->id)
            ->with(['subject', 'components.items'])
            ->firstOrFail();

        return view('grading.configure', compact('gradingClass'));
    }

    public function saveConfiguration(Request $request, $id)
    {
        $request->validate([
            'components' => 'required|array|min:1',
            'components.*.name' => 'required|string',
            'components.*.type' => 'required|string',
            'components.*.weight' => 'required|numeric|min:0|max:100',
            'components.*.formula' => 'required|string',
            'term_formula' => 'nullable|string',
        ]);

        $faculty = auth('faculty')->user();
        $gradingClass = GradingClass::where('id', $id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        // Validate total weight = 100%
        $totalWeight = collect($request->components)->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return back()->with('error', "Total component weights must equal 100%. Current total: {$totalWeight}%");
        }

        DB::beginTransaction();
        try {
            // Update existing components instead of deleting
            foreach ($request->components as $index => $component) {
                GradingComponent::updateOrCreate(
                    [
                        'grading_class_id' => $gradingClass->id,
                        'component_name' => $component['name'],
                    ],
                    [
                        'component_type' => $component['type'],
                        'weight_percentage' => $component['weight'],
                        'formula' => $component['formula'] ?? null,
                        'order' => $index,
                    ]
                );
            }

            // Save term formula and lecture/lab split
            $updateData = [
                'term_formula' => $request->term_formula ? ['formula' => $request->term_formula] : null,
            ];
            
            // Add lecture/lab percentages if this is a lecture_lab subject
            if ($gradingClass->subject->type === 'lecture_lab') {
                $updateData['lecture_percentage'] = $request->lecture_percentage ?? 60;
                $updateData['lab_percentage'] = $request->lab_percentage ?? 40;
            }
            
            $gradingClass->update($updateData);

            // Apply same configuration to other terms (Midterm and Finals)
            $subject = $gradingClass->subject;
            $otherTerms = ['midterm', 'finals'];
            
            foreach ($otherTerms as $term) {
                if ($term === $gradingClass->term) continue;
                
                $otherGradingClass = GradingClass::firstOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'faculty_id' => $faculty->id,
                        'term' => $term,
                    ],
                    [
                        'term_formula' => $request->term_formula ? ['formula' => $request->term_formula] : null,
                    ]
                );
                
                // Copy components to other term
                foreach ($request->components as $index => $component) {
                    GradingComponent::updateOrCreate(
                        [
                            'grading_class_id' => $otherGradingClass->id,
                            'component_name' => $component['name'],
                        ],
                        [
                            'component_type' => $component['type'],
                            'weight_percentage' => $component['weight'],
                            'formula' => $component['formula'] ?? null,
                            'order' => $index,
                        ]
                    );
                }
            }

            // Recalculate all computed scores with new formulas
            $grades = StudentGrade::where('grading_class_id', $gradingClass->id)->get();
            foreach ($grades as $grade) {
                $item = ComponentItem::find($grade->component_item_id);
                if ($item && $item->component && $item->component->formula && $grade->score !== null) {
                    $computedScore = $this->applyFormula(
                        $grade->score,
                        $item->max_score,
                        $item->component->formula
                    );
                    $grade->update(['computed_score' => $computedScore]);
                }
            }

            DB::commit();
            return redirect()->route('grading.grade-sheet', $gradingClass->id)
                ->with('success', 'Configuration saved for all terms and grades recalculated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving grading configuration: ' . $e->getMessage());
            return back()->with('error', 'Error saving configuration: ' . $e->getMessage());
        }
    }

    public function gradeSheet($id)
    {
        $faculty = auth('faculty')->user();
        $gradingClass = GradingClass::where('id', $id)
            ->where('faculty_id', $faculty->id)
            ->with([
                'subject.studentMappings' => function ($query) {
                    $query->whereNotNull('student_id')->whereNotNull('gcr_student_id');
                },
                'components.items.grades',
                'components.items.activity'
            ])
            ->firstOrFail();

        // Get all terms for this subject
        $subject = $gradingClass->subject;
        $prelim = GradingClass::where('subject_id', $subject->id)
            ->where('term', 'prelim')
            ->with(['components.items.grades'])
            ->first();
        $midterm = GradingClass::where('subject_id', $subject->id)
            ->where('term', 'midterm')
            ->with(['components.items.grades'])
            ->first();
        $finals = GradingClass::where('subject_id', $subject->id)
            ->where('term', 'finals')
            ->with(['components.items.grades'])
            ->first();

        // Get students for this class (only matched students)
        $students = StudentMapping::where('subject_id', $gradingClass->subject_id)
            ->whereNotNull('gcr_student_id')
            ->get()
            ->sortBy(function ($student) {
                $csvData = $student->csv_data ?? [];
                $gender = strtoupper($csvData['gender'] ?? 'Z'); // Default 'Z' for unknown gender to sort last
                $name = $student->student_name;
                
                // Sort by gender (M first, then F, then others), then by name
                $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
                return $genderOrder . '_' . $name;
            })
            ->values();

        // Calculate term progress
        $totalStudents = $students->count();
        $studentsWithGrades = 0;

        if ($totalStudents > 0 && $gradingClass->components->count() > 0) {
            foreach ($students as $student) {
                $hasAllGrades = true;
                
                // Check each component
                foreach ($gradingClass->components as $component) {
                    if ($component->component_name === 'Exam') {
                        // Check if exam score exists
                        $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                            ->where('student_mapping_id', $student->id)
                            ->where('component_id', $component->id)
                            ->first();
                        
                        if (!$examGrade || $examGrade->exam_score === null) {
                            $hasAllGrades = false;
                            break;
                        }
                    } else {
                        // Check if all items in this component have grades
                        foreach ($component->items as $item) {
                            $hasGrade = $item->grades->where('student_mapping_id', $student->id)
                                ->where('score', '!=', null)
                                ->isNotEmpty();
                            if (!$hasGrade) {
                                $hasAllGrades = false;
                                break 2; // Break out of both loops
                            }
                        }
                    }
                }
                
                if ($hasAllGrades) {
                    $studentsWithGrades++;
                }
            }
        }

        $completionPercentage = $totalStudents > 0 ? round(($studentsWithGrades / $totalStudents) * 100) : 0;

        return view('grading.grade-sheet', compact('gradingClass', 'students', 'subject', 'prelim', 'midterm', 'finals', 'studentsWithGrades', 'totalStudents', 'completionPercentage'));
    }

    public function addComponentItem(Request $request, $componentId)
    {
        $request->validate([
            'item_name' => 'required|string',
            'max_score' => 'required|numeric|min:0',
            'date' => 'nullable|date',
        ]);

        $component = GradingComponent::findOrFail($componentId);
        
        // Verify ownership
        $faculty = auth('faculty')->user();
        if ($component->gradingClass->faculty_id !== $faculty->id) {
            abort(403);
        }

        // Auto-suggest correct component based on item name
        $suggestedComponent = $this->suggestCorrectComponent($request->item_name, $component->gradingClass);
        $actualComponentId = $suggestedComponent ? $suggestedComponent->id : $component->id;
        
        ComponentItem::create([
            'component_id' => $actualComponentId,
            'item_name' => $request->item_name,
            'max_score' => $request->max_score,
            'date' => $request->date,
        ]);

        $message = 'Item added successfully.';
        if ($suggestedComponent && $suggestedComponent->id !== $component->id) {
            $message .= " Note: Item was automatically moved to '{$suggestedComponent->component_name}' component based on its name.";
        }

        return back()->with('success', $message);
    }

    public function saveGrade(Request $request)
    {
        $request->validate([
            'grading_class_id' => 'required|exists:grading_classes,id',
            'student_mapping_id' => 'required|exists:student_mappings,id',
            'component_item_id' => 'required|exists:component_items,id',
            'score' => 'nullable|numeric|min:0',
        ]);

        $faculty = auth('faculty')->user();
        $gradingClass = GradingClass::where('id', $request->grading_class_id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        $componentItem = ComponentItem::findOrFail($request->component_item_id);
        $component = $componentItem->component;

        // Calculate computed score using formula
        $computedScore = null;
        if ($request->score !== null && $component->formula) {
            $computedScore = $this->applyFormula(
                $request->score,
                $componentItem->max_score,
                $component->formula
            );
        }

        StudentGrade::updateOrCreate(
            [
                'grading_class_id' => $gradingClass->id,
                'student_mapping_id' => $request->student_mapping_id,
                'component_item_id' => $request->component_item_id,
            ],
            [
                'score' => $request->score,
                'computed_score' => $computedScore,
            ]
        );

        // Calculate component totals and term grade for this student
        $componentTotals = [];
        $termGrade = 0;
        
        foreach ($gradingClass->components as $comp) {
            if ($comp->component_type === 'class_standing' && 
                $gradingClass->subject->type === 'lecture_lab' && 
                $gradingClass->lecture_percentage && 
                $gradingClass->lab_percentage) {
                
                // Handle lecture/lab split for class standing components
                $avg = $this->calculateClassStandingWithLectureLab($comp, $request->student_mapping_id, $gradingClass);
            } else {
                // Regular component calculation
                $grades = StudentGrade::where('student_mapping_id', $request->student_mapping_id)
                    ->whereIn('component_item_id', $comp->items->pluck('id'))
                    ->get();
                
                $total = 0;
                $count = 0;
                foreach ($grades as $grade) {
                    if ($grade->computed_score !== null) {
                        $total += $grade->computed_score;
                        $count++;
                    }
                }
                
                $avg = $count > 0 ? $total / $count : null;
            }
            
            $componentTotals[$comp->id] = $avg;
            
            if ($avg !== null) {
                $termGrade += $avg * ($comp->weight_percentage / 100);
            }
        }
        
        return response()->json([
            'success' => true,
            'computed_score' => $computedScore,
            'component_totals' => $componentTotals,
            'term_grade' => round($termGrade, 2),
        ]);
    }

    private function applyFormula($score, $total, $formula)
    {
        try {
            // Replace variables in formula
            $expression = str_replace(['score', 'total'], [$score, $total], $formula);
            
            // Safely evaluate the expression
            $result = eval("return {$expression};");
            
            return round($result, 2);
        } catch (\Exception $e) {
            Log::error('Formula evaluation error: ' . $e->getMessage());
            return null;
        }
    }

    public function saveExamScore(Request $request)
    {
        $request->validate([
            'grading_class_id' => 'required|exists:grading_classes,id',
            'student_mapping_id' => 'required|exists:student_mappings,id',
            'component_id' => 'required|exists:grading_components,id',
            'exam_score' => 'nullable|numeric|min:0',
            'exam_max_score' => 'nullable|numeric|min:0',
        ]);

        $faculty = auth('faculty')->user();
        $gradingClass = GradingClass::where('id', $request->grading_class_id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        $component = GradingComponent::findOrFail($request->component_id);
        
        // Calculate computed score using the configured formula
        $computedScore = null;
        $examScore = $request->exam_score;
        $examMaxScore = $request->exam_max_score ?? 100;
        
        if ($examScore !== null && $examMaxScore > 0) {
            if ($component->formula) {
                // Apply the configured formula (e.g., score/total*60+40)
                $formula = str_replace(['score', 'total'], [$examScore, $examMaxScore], $component->formula);
                try {
                    $computedScore = eval("return {$formula};");
                } catch (\Exception $e) {
                    // Fallback to percentage if formula fails
                    $computedScore = ($examScore / $examMaxScore) * 100;
                }
            } else {
                // Default to percentage calculation
                $computedScore = ($examScore / $examMaxScore) * 100;
            }
        }

        StudentGrade::updateOrCreate(
            [
                'grading_class_id' => $gradingClass->id,
                'student_mapping_id' => $request->student_mapping_id,
                'component_id' => $request->component_id,
            ],
            [
                'exam_score' => $examScore,
                'max_score' => $examMaxScore,
                'computed_score' => $computedScore,
            ]
        );

        return response()->json([
            'success' => true,
            'computed_score' => $computedScore ? round($computedScore, 2) : null
        ]);
    }

    public function updateExamMaxScore(Request $request, $componentId)
    {
        $request->validate([
            'exam_max_score' => 'required|numeric|min:0',
        ]);

        $faculty = auth('faculty')->user();
        $component = GradingComponent::findOrFail($componentId);
        
        // Verify faculty owns this component
        $gradingClass = $component->gradingClass;
        if ($gradingClass->faculty_id !== $faculty->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $component->update(['exam_max_score' => $request->exam_max_score]);

        return response()->json(['success' => true]);
    }

    public function fetchScoresFromGCR($id)
    {
        try {
            $faculty = auth('faculty')->user();
            $gradingClass = GradingClass::where('id', $id)->where('faculty_id', $faculty->id)->firstOrFail();
            $subject = $gradingClass->subject;

            if (!$subject->gcr_class_id) {
                return response()->json(['success' => false, 'message' => 'Subject not connected to Google Classroom']);
            }

            $classroomService = app(GoogleClassroomService::class);
            if (!$classroomService->authenticateWithFaculty($faculty)) {
                return response()->json(['success' => false, 'message' => 'Failed to authenticate with Google Classroom']);
            }

            // First, import and categorize new assignments from Google Classroom
            $this->importAndCategorizeAssignments($gradingClass, $classroomService);

            $importedCount = 0;
            $skippedNoActivityId = 0;
            $skippedNoGcrId = 0;
            
            foreach ($gradingClass->components as $component) {
                foreach ($component->items as $item) {
                    if (!$item->activity_id) {
                        $skippedNoActivityId++;
                        continue;
                    }
                    
                    $activity = Activity::find($item->activity_id);
                    if (!$activity || !$activity->gcr_assignment_id) {
                        $skippedNoGcrId++;
                        continue;
                    }

                    Log::info('Fetching submissions', ['activity' => $activity->name, 'gcr_id' => $activity->gcr_assignment_id]);
                    $submissions = $classroomService->getStudentSubmissions($subject->gcr_class_id, $activity->gcr_assignment_id);
                    Log::info('Got submissions', ['count' => count($submissions)]);
                    
                    foreach ($submissions as $submission) {
                        $gcrUserId = $submission['userId'] ?? $submission['user_id'] ?? null;
                        $score = $submission['assignedGrade'] ?? $submission['assigned_grade'] ?? null;
                        
                        Log::info('Processing submission', ['user_id' => $gcrUserId, 'score' => $score]);
                        
                        if (!$gcrUserId || $score === null) continue;

                        $studentMapping = StudentMapping::where('subject_id', $subject->id)->where('gcr_student_id', $gcrUserId)->first();
                        if (!$studentMapping) {
                            Log::warning('Student mapping not found', ['gcr_user_id' => $gcrUserId]);
                            continue;
                        }

                        $computedScore = ($score / $activity->max_score) * 100;
                        
                        StudentGrade::updateOrCreate(
                            ['component_item_id' => $item->id, 'student_mapping_id' => $studentMapping->id],
                            [
                                'grading_class_id' => $gradingClass->id,
                                'score' => $score, 
                                'computed_score' => $computedScore
                            ]
                        );
                        $importedCount++;
                    }
                }
            }
            
            Log::info('Fetch complete', ['imported' => $importedCount, 'skipped_no_activity_id' => $skippedNoActivityId, 'skipped_no_gcr_id' => $skippedNoGcrId]);

            return response()->json(['success' => true, 'message' => "Imported {$importedCount} scores from Google Classroom"]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch scores from GCR', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function updateMaxScore(Request $request, $itemId)
    {
        $request->validate([
            'max_score' => 'required|numeric|min:0.01',
        ]);

        try {
            $faculty = auth('faculty')->user();
            
            // Get the component item
            $item = ComponentItem::findOrFail($itemId);
            $component = $item->component;
            $gradingClass = $component->gradingClass;
            
            // Verify faculty owns this grading class
            if ($gradingClass->faculty_id !== $faculty->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $oldMaxScore = $item->max_score;
            $newMaxScore = $request->max_score;
            
            // Update the max score
            $item->max_score = $newMaxScore;
            $item->save();
            
            // Recalculate all computed scores for this item
            $grades = StudentGrade::where('component_item_id', $itemId)->get();
            
            foreach ($grades as $grade) {
                if ($grade->score !== null && $component->formula) {
                    $computedScore = $this->applyFormula(
                        $grade->score,
                        $newMaxScore,
                        $component->formula
                    );
                    $grade->computed_score = $computedScore;
                    $grade->save();
                }
            }
            
            Log::info('Updated max score', [
                'item_id' => $itemId,
                'old_max' => $oldMaxScore,
                'new_max' => $newMaxScore,
                'grades_recalculated' => $grades->count()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Max score updated and grades recalculated',
                'max_score' => $newMaxScore
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update max score', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function fullMatrix(Request $request, $subjectId)
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        $subject = Subject::where('id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->with('matrixComponents.scores')
            ->firstOrFail();
        
        // Get all grading classes for this subject (prelim, midterm, finals)
        $gradingClasses = GradingClass::where('subject_id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->with(['components'])
            ->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')")
            ->get();
        
        if ($gradingClasses->isEmpty()) {
            return redirect()->route('grading.index')->with('error', 'No grading classes found for this subject.');
        }
        
        // Get only matched students (those with gcr_student_id)
        $students = StudentMapping::where('subject_id', $subjectId)
            ->whereNotNull('gcr_student_id')
            ->get()
            ->sortBy(function ($student) {
                $csvData = $student->csv_data ?? [];
                $gender = strtoupper($csvData['gender'] ?? 'Z'); // Default 'Z' for unknown gender to sort last
                $name = $student->student_name;
                
                // Sort by gender (M first, then F, then others), then by name
                $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
                return $genderOrder . '_' . $name;
            })
            ->values();
        
        // Get custom matrix components
        $matrixComponents = $subject->matrixComponents;
        
        // Get final rating formula
        $savedFormula = $subject->final_rating_formula;
        
        if ($savedFormula && isset($savedFormula['components'])) {
            $finalRatingFormula = $savedFormula['components'];
            $termWeights = $savedFormula['term_weights'];
        } else {
            $finalRatingFormula = $savedFormula ?? ['final_grade' => 100];
            $termWeights = ['prelim' => 30, 'midterm' => 30, 'finals' => 40];
        }
        
        return view('grading.full-matrix', compact('subject', 'gradingClasses', 'students', 'finalRatingFormula', 'termWeights', 'matrixComponents'));
    }

    public function updateFinalRatingFormula(Request $request, $subjectId)
    {
        $request->validate([
            'components' => 'required|array',
            'components.*.name' => 'required|string',
            'components.*.weight' => 'required|numeric|min:0|max:100',
            'term_weights' => 'required|array',
            'component_formulas' => 'required|array',
        ]);
        
        // Validate that all components (except final_grade) have formulas
        foreach ($request->components as $component) {
            if ($component['name'] !== 'final_grade') {
                if (!isset($request->component_formulas[$component['name']]) || 
                    empty(trim($request->component_formulas[$component['name']]))) {
                    return response()->json([
                        'success' => false,
                        'message' => "Formula is required for component: {$component['name']}"
                    ], 400);
                }
            }
        }
        
        $total = collect($request->components)->sum('weight');
        if ($total != 100) {
            return response()->json([
                'success' => false,
                'message' => 'Percentages must total 100%'
            ], 400);
        }
        
        $faculty = auth('faculty')->user();
        $subject = Subject::where('id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();
        
        $formula = [
            'components' => [],
            'term_weights' => $request->term_weights
        ];
        
        foreach ($request->components as $component) {
            $formula['components'][$component['name']] = $component['weight'];
        }
        
        $subject->update(['final_rating_formula' => $formula]);
        
        // Update formulas for existing matrix components
        if ($request->component_formulas) {
            foreach ($request->component_formulas as $componentName => $componentFormula) {
                MatrixComponent::where('subject_id', $subjectId)
                    ->where('component_name', $componentName)
                    ->update(['formula' => $componentFormula]);
            }
        }
        
        return response()->json(['success' => true]);
    }

    public function addMatrixComponent(Request $request, $subjectId)
    {
        try {
            $request->validate([
                'component_name' => 'required|string|max:255',
                'max_score' => 'required|numeric|min:0',
            ]);
            
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            if (!$faculty) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }
            
            $subject = Subject::where('id', $subjectId)
                ->where('faculty_id', $faculty->id)
                ->firstOrFail();
        
        $maxOrder = MatrixComponent::where('subject_id', $subjectId)->max('order') ?? 0;
        
            $component = MatrixComponent::create([
                'subject_id' => $subjectId,
                'component_name' => $request->component_name,
                'max_score' => $request->max_score,
                'order' => $maxOrder + 1,
            ]);
            
            return response()->json(['success' => true, 'component' => $component]);
            
        } catch (\Exception $e) {
            Log::error('Error adding matrix component: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveMatrixComponentScore(Request $request, $componentId)
    {
        $request->validate([
            'student_mapping_id' => 'required|exists:student_mappings,id',
            'score' => 'nullable|numeric|min:0',
        ]);
        
        $component = MatrixComponent::findOrFail($componentId);
        
        MatrixComponentScore::updateOrCreate(
            [
                'matrix_component_id' => $componentId,
                'student_mapping_id' => $request->student_mapping_id,
            ],
            [
                'score' => $request->score,
            ]
        );
        
        return response()->json(['success' => true]);
    }

    public function deleteMatrixComponent($componentId)
    {
        $component = MatrixComponent::findOrFail($componentId);
        $component->delete();
        
        return response()->json(['success' => true]);
    }

    public function exportFullMatrixCsv(Request $request, $subjectId)
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        $subject = Subject::where('id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->with('matrixComponents.scores')
            ->firstOrFail();
        
        // Get grading classes
        $gradingClasses = GradingClass::where('subject_id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->with(['components.items'])
            ->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')")
            ->get();
        
        // Get students sorted by gender (M first, then F) and then by name
        $students = StudentMapping::where('subject_id', $subjectId)
            ->whereNotNull('gcr_student_id')
            ->get()
            ->sortBy(function ($student) {
                $csvData = $student->csv_data ?? [];
                $gender = strtoupper($csvData['gender'] ?? 'Z'); // Default 'Z' for unknown gender to sort last
                $name = $student->student_name;
                
                // Sort by gender (M first, then F, then others), then by name
                $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
                return $genderOrder . '_' . $name;
            })
            ->values();
        
        // Get matrix components
        $matrixComponents = $subject->matrixComponents;
        
        // Get formula
        $savedFormula = $subject->final_rating_formula;
        if ($savedFormula && isset($savedFormula['components'])) {
            $finalRatingFormula = $savedFormula['components'];
            $termWeights = $savedFormula['term_weights'];
        } else {
            $finalRatingFormula = $savedFormula ?? ['final_grade' => 100];
            $termWeights = ['prelim' => 30, 'midterm' => 30, 'finals' => 40];
        }
        
        // Build CSV
        $filename = $subject->subject_code . '_Full_Matrix_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($students, $gradingClasses, $matrixComponents, $finalRatingFormula, $termWeights, $subject, $faculty) {
            $file = fopen('php://output', 'w');
            
            // Subject information header
            $semesterText = $subject->semester == 1 ? 'First' : ($subject->semester == 2 ? 'Second' : 'Summer');
            $department = $gradingClasses->first()->department ?? '';
            
            fputcsv($file, ['Subject:', $subject->subject_code . '_' . $subject->subject_name]);
            fputcsv($file, ['Term:', $semesterText . ' Semester ' . $subject->academic_year]);
            fputcsv($file, ['Teacher:', $faculty->name]);
            fputcsv($file, ['Department:', $department]);
            fputcsv($file, ['For:', $subject->section]);
            fputcsv($file, []); // Empty row for spacing
            
            // Header row
            $header = ['#', 'ID No.', 'Full Name', 'Gender', 'Course', 'YL'];
            foreach ($gradingClasses as $class) {
                $header[] = ucfirst($class->term);
            }
            if ($matrixComponents->count() > 0) {
                $header[] = 'Term Grade';
            }
            foreach ($matrixComponents as $component) {
                $header[] = $component->component_name;
            }
            $header[] = 'Final Grade (Raw)';
            $header[] = 'Final Rating (Rounded)';
            $header[] = 'Status';
            
            fputcsv($file, $header);
            
            // Data rows
            $rowNum = 1;
            foreach ($students as $student) {
                $csvData = $student->csv_data ?? [];
                $row = [
                    $rowNum++,
                    $csvData['id_no'] ?? '',
                    $student->student_name,
                    $csvData['gender'] ?? '',
                    $csvData['course'] ?? '',
                    $csvData['yl'] ?? '',
                ];
                
                // Calculate grades
                $termGradesTotal = 0;
                $termGradesCount = 0;
                $finalRating = 0;
                
                foreach ($gradingClasses as $gradingClass) {
                    $termGrade = $this->calculateTermGradeForExport($student, $gradingClass);
                    $row[] = $termGrade > 0 ? number_format($termGrade, 2) : '';
                    $termGradesTotal += $termGrade * ($termWeights[$gradingClass->term] / 100);
                    $termGradesCount++;
                }
                
                $finalGrade = $termGradesCount > 0 ? $termGradesTotal : 0;
                
                if ($matrixComponents->count() > 0) {
                    $row[] = $finalGrade > 0 ? number_format($finalGrade, 2) : '';
                    $finalRating = $finalGrade * ($finalRatingFormula['final_grade'] / 100);
                } else {
                    $finalRating = $finalGrade;
                }
                
                foreach ($matrixComponents as $component) {
                    $score = $component->scores->where('student_mapping_id', $student->id)->first();
                    $row[] = $score && $score->score !== null ? $score->score : '';
                    
                    if ($score && $score->score !== null) {
                        if ($component->formula) {
                            try {
                                $formula = str_replace(['score', 'total'], [$score->score, $component->max_score], $component->formula);
                                $componentGrade = eval("return {$formula};");
                            } catch (\Exception $e) {
                                $componentGrade = ($score->score / $component->max_score) * 100;
                            }
                        } else {
                            $componentGrade = ($score->score / $component->max_score) * 100;
                        }
                        if (isset($finalRatingFormula[$component->component_name])) {
                            $finalRating += $componentGrade * ($finalRatingFormula[$component->component_name] / 100);
                        }
                    }
                }
                
                // Add Final Grade (raw with decimals)
                $row[] = $finalRating > 0 ? number_format($finalRating, 2) : '';
                
                // Check if there's a saved final rating in the database
                $savedFinalRating = \App\Models\FinalRating::where('student_mapping_id', $student->id)
                    ->where('subject_id', $subject->id)
                    ->first();
                $displayRating = $savedFinalRating && $savedFinalRating->final_rating !== null 
                    ? round($savedFinalRating->final_rating) 
                    : round($finalRating);
                
                // Add Final Rating (rounded, using saved value if exists)
                $row[] = $displayRating > 0 ? $displayRating : '';
                $row[] = $displayRating >= 75 ? 'Passed' : ($displayRating > 0 ? 'Failed' : '');
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function calculateTermGradeForExport($student, $gradingClass)
    {
        $termGrade = 0;
        $totalWeight = 0;
        
        foreach($gradingClass->components as $component) {
            if ($component->component_name === 'Exam') {
                $examGrade = StudentGrade::where('grading_class_id', $gradingClass->id)
                    ->where('student_mapping_id', $student->id)
                    ->where('component_id', $component->id)
                    ->first();
                
                if ($examGrade && $examGrade->exam_score !== null) {
                    // Use computed_score if available (applies configured formula)
                    if ($examGrade->computed_score !== null) {
                        $examComputedScore = $examGrade->computed_score;
                    } else {
                        // Fallback to raw percentage calculation
                        $examMaxScore = $component->exam_max_score ?? 100;
                        $examComputedScore = ($examGrade->exam_score / $examMaxScore) * 100;
                    }
                    $termGrade += $examComputedScore * ($component->weight_percentage / 100);
                    $totalWeight += $component->weight_percentage;
                }
            } else {
                $grades = StudentGrade::where('student_mapping_id', $student->id)
                    ->whereIn('component_item_id', $component->items->pluck('id'))
                    ->get();
                
                $total = 0;
                $count = 0;
                foreach ($grades as $grade) {
                    if ($grade->computed_score !== null) {
                        $total += $grade->computed_score;
                        $count++;
                    }
                }
                
                if ($count > 0) {
                    $avg = $total / $count;
                    $termGrade += $avg * ($component->weight_percentage / 100);
                    $totalWeight += $component->weight_percentage;
                }
            }
        }
        
        return $termGrade;
    }

    private function autoCheckArchivedSubjects($faculty)
    {
        try {
            // Get all active subjects with GCR connections for this faculty
            $subjects = Subject::where('faculty_id', $faculty->id)
                ->whereNotNull('gcr_class_id')
                ->whereNull('archived_at')
                ->get();

            if ($subjects->isEmpty()) {
                return;
            }

            $classroomService = app(\App\Services\GoogleClassroomService::class);
            
            // Try to authenticate with faculty's Google account
            if (!$classroomService->authenticateWithFaculty($faculty)) {
                return; // Skip if can't authenticate
            }

            foreach ($subjects as $subject) {
                $classroomService->checkAndArchiveSubject($subject);
            }
        } catch (\Exception $e) {
            // Silently fail - don't interrupt the user experience
            Log::error('Auto-archive check failed', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function exportFullMatrixPdf(Request $request, $subjectId)
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        $subject = Subject::where('id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->with('matrixComponents.scores')
            ->firstOrFail();
        
        // Get grading classes
        $gradingClasses = GradingClass::where('subject_id', $subjectId)
            ->where('faculty_id', $faculty->id)
            ->with(['components.items'])
            ->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')")
            ->get();
        
        // Get students sorted by gender (M first, then F) and then by name
        $students = StudentMapping::where('subject_id', $subjectId)
            ->whereNotNull('gcr_student_id')
            ->get()
            ->sortBy(function ($student) {
                $csvData = $student->csv_data ?? [];
                $gender = strtoupper($csvData['gender'] ?? 'Z'); // Default 'Z' for unknown gender to sort last
                $name = $student->student_name;
                
                // Sort by gender (M first, then F, then others), then by name
                $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
                return $genderOrder . '_' . $name;
            })
            ->values();
        
        // Get matrix components
        $matrixComponents = $subject->matrixComponents;
        
        // Get formula
        $savedFormula = $subject->final_rating_formula;
        if ($savedFormula && isset($savedFormula['components'])) {
            $finalRatingFormula = $savedFormula['components'];
            $termWeights = $savedFormula['term_weights'];
        } else {
            $finalRatingFormula = $savedFormula ?? ['final_grade' => 100];
            $termWeights = ['prelim' => 30, 'midterm' => 30, 'finals' => 40];
        }
        
        $pdf = \PDF::loadView('grading.exports.full-matrix-pdf', compact(
            'subject',
            'faculty',
            'students',
            'gradingClasses',
            'matrixComponents',
            'finalRatingFormula',
            'termWeights'
        ));
        
        $pdf->setPaper('legal', 'landscape');
        
        $filename = $subject->subject_code . '_Full_Matrix_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Import and categorize assignments from Google Classroom
     */
    private function importAndCategorizeAssignments($gradingClass, $classroomService)
    {
        try {
            $subject = $gradingClass->subject;
            
            // Get categorized coursework from Google Classroom
            $categorizedWork = $classroomService->getCategorizedCourseWork($subject->gcr_class_id);
            
            // Find Activities and Quizzes components
            $activitiesComponent = $gradingClass->components->where('component_name', 'Activities')->first();
            $quizzesComponent = $gradingClass->components->where('component_name', 'Quizzes')->first();
            
            if (!$activitiesComponent || !$quizzesComponent) {
                Log::info('No Activities or Quizzes components found for auto-categorization');
                return;
            }
            
            // Import activities
            foreach ($categorizedWork['activities'] as $work) {
                $this->importAssignmentToComponent($work, $activitiesComponent, $subject);
            }
            
            // Import quizzes
            foreach ($categorizedWork['quizzes'] as $work) {
                $this->importAssignmentToComponent($work, $quizzesComponent, $subject);
            }
            
        } catch (\Exception $e) {
            Log::error('Error importing and categorizing assignments', [
                'grading_class_id' => $gradingClass->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Import a single assignment to a specific component
     */
    private function importAssignmentToComponent($work, $component, $subject)
    {
        try {
            // Determine if this assignment belongs to this term based on naming
            $assignmentTerm = $this->determineAssignmentTerm($work['title']);
            $currentTerm = $component->gradingClass->term;
            
            // Only import if the assignment belongs to this term
            if ($assignmentTerm && $assignmentTerm !== $currentTerm) {
                Log::info('Skipping assignment - wrong term', [
                    'assignment' => $work['title'],
                    'assignment_term' => $assignmentTerm,
                    'current_term' => $currentTerm
                ]);
                return;
            }
            
            // Check if activity already exists
            $existingActivity = Activity::where('subject_id', $subject->id)
                ->where('gcr_assignment_id', $work['id'])
                ->first();
            
            if (!$existingActivity) {
                // Create new activity
                $existingActivity = Activity::create([
                    'subject_id' => $subject->id,
                    'name' => $work['title'],
                    'description' => $work['description'] ?? '',
                    'max_score' => $work['max_points'] ?? 100,
                    'gcr_assignment_id' => $work['id'],
                    'type' => 'lecture', // Default type
                    'term' => $currentTerm
                ]);
            }
            
            // Check if component item already exists in ANY component for this grading class
            $existingItem = ComponentItem::whereHas('component', function($query) use ($component) {
                    $query->where('grading_class_id', $component->grading_class_id);
                })
                ->where('activity_id', $existingActivity->id)
                ->first();
            
            if (!$existingItem) {
                // Create component item
                ComponentItem::create([
                    'component_id' => $component->id,
                    'item_name' => $work['title'],
                    'max_score' => $work['max_points'] ?? 100,
                    'activity_id' => $existingActivity->id,
                    'date' => now()
                ]);
                
                Log::info('Auto-categorized assignment', [
                    'assignment' => $work['title'],
                    'component' => $component->component_name,
                    'term' => $currentTerm
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error importing assignment to component', [
                'assignment_id' => $work['id'],
                'assignment_title' => $work['title'],
                'component_id' => $component->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Determine which term an assignment belongs to based on its name
     */
    private function determineAssignmentTerm($assignmentName)
    {
        $name = strtolower($assignmentName);
        
        // Prelim patterns
        if (preg_match('/^a\d+|prelim|^quiz\s*\d*$/i', $assignmentName)) {
            return 'prelim';
        }
        
        // Midterm patterns
        if (preg_match('/^m\d+|^mq\d+|midterm/i', $assignmentName)) {
            return 'midterm';
        }
        
        // Finals patterns
        if (preg_match('/^f\d+|^fq\d+|finals?/i', $assignmentName)) {
            return 'finals';
        }
        
        // If no pattern matches, return null (import to current term)
        return null;
    }

    /**
     * Move an item to a different component
     */
    public function moveItemToComponent(Request $request, $itemId)
    {
        $request->validate([
            'target_component_id' => 'required|exists:grading_components,id',
        ]);

        $faculty = auth('faculty')->user();
        
        $item = ComponentItem::findOrFail($itemId);
        $currentComponent = $item->component;
        $targetComponent = GradingComponent::findOrFail($request->target_component_id);
        
        // Verify faculty owns both components
        if ($currentComponent->gradingClass->faculty_id !== $faculty->id || 
            $targetComponent->gradingClass->faculty_id !== $faculty->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // Verify both components belong to the same grading class
        if ($currentComponent->grading_class_id !== $targetComponent->grading_class_id) {
            return response()->json(['success' => false, 'message' => 'Components must be from the same term'], 400);
        }
        
        $item->update(['component_id' => $request->target_component_id]);
        
        return response()->json([
            'success' => true,
            'message' => "Item '{$item->item_name}' moved from '{$currentComponent->component_name}' to '{$targetComponent->component_name}'"
        ]);
    }

    /**
     * Suggest the correct component based on item name patterns
     */
    private function suggestCorrectComponent($itemName, $gradingClass)
    {
        $components = $gradingClass->components;
        
        // Find Activities and Quizzes components
        $activitiesComponent = $components->where('component_name', 'Activities')->first();
        $quizzesComponent = $components->where('component_name', 'Quizzes')->first();
        
        if (!$activitiesComponent || !$quizzesComponent) {
            return null; // No auto-suggestion if components don't exist
        }
        
        // Check if item name suggests it's a quiz
        if (preg_match('/Q\d+|quiz/i', $itemName)) {
            return $quizzesComponent;
        }
        
        // Check if item name suggests it's an activity
        if (preg_match('/^[A-Z]\d+$|activity/i', $itemName) && !preg_match('/Q|quiz/i', $itemName)) {
            return $activitiesComponent;
        }
        
        return null; // No suggestion
    }

    public function exportTermCsv(Request $request, $gradingClassId)
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            $gradingClass = GradingClass::where('id', $gradingClassId)
                ->where('faculty_id', $faculty->id)
                ->with(['subject', 'components.items.grades'])
                ->firstOrFail();
            
            $subject = $gradingClass->subject;
        
        // Get students for this class
        $students = StudentMapping::where('subject_id', $subject->id)
            ->whereNotNull('gcr_student_id')
            ->orderBy('student_name')
            ->get();
        
        $filename = "{$subject->subject_code}_{$gradingClass->term}_grades_" . date('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($gradingClass, $students) {
            $file = fopen('php://output', 'w');
            
            // Header row
            $header = ['Student Name'];
            
            // Add component item headers
            foreach ($gradingClass->components as $component) {
                foreach ($component->items as $item) {
                    $header[] = $item->item_name . ' (Raw)';
                    $header[] = $item->item_name . ' (Computed)';
                }
                $header[] = $component->component_name . ' Average';
            }
            $header[] = 'Term Grade';
            
            fputcsv($file, $header);
            
            // Data rows
            foreach ($students as $student) {
                $row = [$student->student_name];
                $termGradeComponents = [];
                
                foreach ($gradingClass->components as $component) {
                    if ($component->component_type === 'class_standing' && 
                        $gradingClass->subject->type === 'lecture_lab' && 
                        $gradingClass->lecture_percentage && 
                        $gradingClass->lab_percentage) {
                        
                        // Handle lecture/lab split for class standing components
                        foreach ($component->items as $item) {
                            $grade = $item->grades->where('student_mapping_id', $student->id)->first();
                            
                            // Raw score
                            $row[] = $grade ? ($grade->score ?? '') : '';
                            
                            // Computed score
                            $row[] = $grade ? ($grade->computed_score ?? '') : '';
                        }
                        
                        $componentAvg = $this->calculateClassStandingWithLectureLab($component, $student->id, $gradingClass);
                    } else {
                        // Regular component calculation
                        $componentTotal = 0;
                        $componentCount = 0;
                        
                        foreach ($component->items as $item) {
                            $grade = $item->grades->where('student_mapping_id', $student->id)->first();
                            
                            // Raw score
                            $row[] = $grade ? ($grade->score ?? '') : '';
                            
                            // Computed score
                            $row[] = $grade ? ($grade->computed_score ?? '') : '';
                            
                            if ($grade && $grade->computed_score !== null) {
                                $componentTotal += $grade->computed_score;
                                $componentCount++;
                            }
                        }
                        
                        // Component average
                        $componentAvg = $componentCount > 0 ? $componentTotal / $componentCount : null;
                    }
                    
                    $row[] = $componentAvg !== null ? number_format($componentAvg, 2) : '';
                    
                    if ($componentAvg !== null) {
                        $termGradeComponents[] = $componentAvg * ($component->weight_percentage / 100);
                    }
                }
                
                // Term grade
                $termGrade = count($termGradeComponents) > 0 ? array_sum($termGradeComponents) : null;
                $row[] = $termGrade !== null ? number_format($termGrade, 2) : '';
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
        
        } catch (\Exception $e) {
            Log::error('Error exporting term CSV', [
                'grading_class_id' => $gradingClassId,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to export CSV: ' . $e->getMessage());
        }
    }

    public function exportTermPdf(Request $request, $gradingClassId)
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            $gradingClass = GradingClass::where('id', $gradingClassId)
                ->where('faculty_id', $faculty->id)
                ->with(['subject', 'components.items.grades'])
                ->firstOrFail();
            
            $subject = $gradingClass->subject;
            
            // Get students for this class
            $students = StudentMapping::where('subject_id', $subject->id)
                ->whereNotNull('gcr_student_id')
                ->orderBy('student_name')
                ->get();
            
            $pdf = \PDF::loadView('grading.exports.term-pdf', compact('gradingClass', 'subject', 'students', 'faculty'));
            
            $pdf->setPaper('legal', 'landscape');
            
            $filename = $subject->subject_code . '_' . ucfirst($gradingClass->term) . '_Grades_' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Error exporting term PDF', [
                'grading_class_id' => $gradingClassId,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    public function saveFinalRating(Request $request)
    {
        try {
            $request->validate([
                'student_mapping_id' => 'required|exists:student_mappings,id',
                'subject_id' => 'required|exists:subjects,id',
                'final_rating' => 'nullable|numeric|min:0|max:100'
            ]);

            $studentMapping = StudentMapping::findOrFail($request->student_mapping_id);
            $subject = Subject::findOrFail($request->subject_id);

            // Get or create final rating record
            $finalRating = \App\Models\FinalRating::updateOrCreate(
                [
                    'student_mapping_id' => $request->student_mapping_id,
                    'subject_id' => $request->subject_id,
                    'academic_year' => config('app.current_academic_year', '2024-2025'),
                    'semester' => config('app.current_semester', '1')
                ],
                [
                    'final_rating' => $request->final_rating
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Final rating saved successfully',
                'final_rating' => $finalRating->final_rating
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving final rating: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving final rating: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAllTermsPdf(Request $request, $subjectId)
    {
        try {
            $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
            
            $subject = Subject::where('id', $subjectId)
                ->where('faculty_id', $faculty->id)
                ->firstOrFail();
            
            // Get all grading classes for this subject (Prelim, Midterm, Finals)
            $gradingClasses = GradingClass::where('subject_id', $subjectId)
                ->where('faculty_id', $faculty->id)
                ->with(['components.items.grades'])
                ->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')")
                ->get();
            
            // Get students sorted by gender
            $students = StudentMapping::where('subject_id', $subjectId)
                ->whereNotNull('gcr_student_id')
                ->get()
                ->sortBy(function ($student) {
                    $csvData = $student->csv_data ?? [];
                    $gender = strtoupper($csvData['gender'] ?? 'Z');
                    $name = $student->student_name;
                    $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
                    return $genderOrder . '_' . $name;
                })
                ->values();
            
            $pdf = \PDF::loadView('grading.exports.all-terms-pdf', compact(
                'subject',
                'faculty',
                'gradingClasses',
                'students'
            ));
            
            $pdf->setPaper('legal', 'landscape');
            
            $filename = $subject->subject_code . '_All_Terms_' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Error exporting all terms PDF', [
                'subject_id' => $subjectId,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    public function saveLectureLabExamScore(Request $request)
    {
        $request->validate([
            'grading_class_id' => 'required|exists:grading_classes,id',
            'student_mapping_id' => 'required|exists:student_mappings,id',
            'component_id' => 'required|exists:grading_components,id',
            'exam_type' => 'required|in:lecture,lab',
            'exam_score' => 'nullable|numeric|min:0',
            'exam_max_score' => 'nullable|numeric|min:0',
        ]);

        $faculty = auth('faculty')->user();
        $gradingClass = GradingClass::where('id', $request->grading_class_id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        // Only allow this for lecture_lab subjects
        if ($gradingClass->subject->type !== 'lecture_lab') {
            return response()->json([
                'success' => false,
                'message' => 'This feature is only available for Lecture+Lab subjects.'
            ], 400);
        }

        $component = GradingComponent::findOrFail($request->component_id);
        
        try {
            // Get or create the student grade record
            $studentGrade = StudentGrade::firstOrCreate(
                [
                    'grading_class_id' => $gradingClass->id,
                    'student_mapping_id' => $request->student_mapping_id,
                    'component_id' => $request->component_id,
                ],
                [
                    'max_score' => $request->exam_max_score ?? 100,
                ]
            );

            // Update the appropriate exam score field
            if ($request->exam_type === 'lecture') {
                $studentGrade->lecture_exam_score = $request->exam_score;
            } else {
                $studentGrade->lab_exam_score = $request->exam_score;
            }

            // Calculate total exam score and computed score
            $lectureScore = $studentGrade->lecture_exam_score ?? 0;
            $labScore = $studentGrade->lab_exam_score ?? 0;
            $totalScore = $lectureScore + $labScore;
            $maxScore = ($request->exam_max_score ?? 100) * 2; // Both lecture and lab have same max score

            // Update total exam score
            $studentGrade->exam_score = $totalScore > 0 ? $totalScore : null;

            // Calculate computed score using the configured formula if both scores exist
            $computedScore = null;
            if ($studentGrade->lecture_exam_score !== null && $studentGrade->lab_exam_score !== null) {
                // Apply lecture/lab split to exam calculation
                if ($gradingClass->lecture_percentage && $gradingClass->lab_percentage) {
                    $lectureWeight = $gradingClass->lecture_percentage / 100;
                    $labWeight = $gradingClass->lab_percentage / 100;
                    
                    // Calculate weighted exam score
                    $weightedScore = ($lectureScore * $lectureWeight) + ($labScore * $labWeight);
                    
                    // Apply component formula if exists
                    if ($component->formula) {
                        $formula = str_replace(['score', 'total'], [$weightedScore, $request->exam_max_score ?? 100], $component->formula);
                        try {
                            $computedScore = eval("return {$formula};");
                        } catch (\Exception $e) {
                            // Fallback to percentage if formula fails
                            $computedScore = ($weightedScore / ($request->exam_max_score ?? 100)) * 100;
                        }
                    } else {
                        // Default to percentage calculation
                        $computedScore = ($weightedScore / ($request->exam_max_score ?? 100)) * 100;
                    }
                } else {
                    // Fallback: use total score with equal weighting
                    if ($component->formula) {
                        $formula = str_replace(['score', 'total'], [$totalScore, $maxScore], $component->formula);
                        try {
                            $computedScore = eval("return {$formula};");
                        } catch (\Exception $e) {
                            $computedScore = ($totalScore / $maxScore) * 100;
                        }
                    } else {
                        $computedScore = ($totalScore / $maxScore) * 100;
                    }
                }
            }

            $studentGrade->computed_score = $computedScore ? round($computedScore, 2) : null;
            $studentGrade->save();

            Log::info('Lecture/Lab exam score saved', [
                'grading_class_id' => $gradingClass->id,
                'student_mapping_id' => $request->student_mapping_id,
                'component_id' => $request->component_id,
                'exam_type' => $request->exam_type,
                'exam_score' => $request->exam_score,
                'total_score' => $totalScore,
                'computed_score' => $computedScore
            ]);

            return response()->json([
                'success' => true,
                'computed_score' => $computedScore ? round($computedScore, 2) : null,
                'total_score' => $totalScore,
                'lecture_score' => $studentGrade->lecture_exam_score,
                'lab_score' => $studentGrade->lab_exam_score,
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving lecture/lab exam score', [
                'grading_class_id' => $request->grading_class_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving exam score: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate class standing component with lecture/lab split
     */
    private function calculateClassStandingWithLectureLab($component, $studentMappingId, $gradingClass)
    {
        // Get all grades for this component
        $grades = StudentGrade::where('student_mapping_id', $studentMappingId)
            ->whereIn('component_item_id', $component->items->pluck('id'))
            ->with('componentItem.activity')
            ->get();

        // Separate lecture and lab grades
        $lectureGrades = $grades->filter(function($grade) {
            return $grade->componentItem && 
                   $grade->componentItem->activity && 
                   $grade->componentItem->activity->type === 'lecture';
        });

        $labGrades = $grades->filter(function($grade) {
            return $grade->componentItem && 
                   $grade->componentItem->activity && 
                   $grade->componentItem->activity->type === 'lab';
        });

        // Calculate lecture average
        $lectureTotal = 0;
        $lectureCount = 0;
        foreach ($lectureGrades as $grade) {
            if ($grade->computed_score !== null) {
                $lectureTotal += $grade->computed_score;
                $lectureCount++;
            }
        }
        $lectureAvg = $lectureCount > 0 ? $lectureTotal / $lectureCount : 0;

        // Calculate lab average
        $labTotal = 0;
        $labCount = 0;
        foreach ($labGrades as $grade) {
            if ($grade->computed_score !== null) {
                $labTotal += $grade->computed_score;
                $labCount++;
            }
        }
        $labAvg = $labCount > 0 ? $labTotal / $labCount : 0;

        // Apply lecture/lab split percentages
        $lecturePercentage = $gradingClass->lecture_percentage / 100;
        $labPercentage = $gradingClass->lab_percentage / 100;

        // Calculate weighted class standing
        $classStanding = ($lectureAvg * $lecturePercentage) + ($labAvg * $labPercentage);

        Log::info('Class standing calculation with lecture/lab split', [
            'component_id' => $component->id,
            'student_mapping_id' => $studentMappingId,
            'lecture_avg' => $lectureAvg,
            'lab_avg' => $labAvg,
            'lecture_percentage' => $gradingClass->lecture_percentage,
            'lab_percentage' => $gradingClass->lab_percentage,
            'final_class_standing' => $classStanding
        ]);

        return $classStanding;
    }

}
