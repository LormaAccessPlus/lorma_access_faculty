<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradingClass;
use App\Models\GradingComponent;
use App\Models\ComponentItem;
use App\Models\StudentGrade;
use App\Models\StudentMapping;
use App\Models\Activity;
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
        
        // Get grading classes for this faculty
        $gradingClasses = GradingClass::where('faculty_id', $faculty->id)
            ->with(['subject', 'components.items'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available subjects (not yet added as grading classes)
        $availableSubjects = Subject::where('faculty_id', $faculty->id)
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
            'components.*.formula' => 'nullable|string',
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

            // Save term formula
            $gradingClass->update([
                'term_formula' => $request->term_formula ? ['formula' => $request->term_formula] : null,
            ]);

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

        // Get students for this class (only GCR matched students)
        $students = StudentMapping::where('subject_id', $gradingClass->subject_id)
            ->whereNotNull('gcr_student_id')
            ->orderBy('student_name')
            ->get();

        return view('grading.grade-sheet', compact('gradingClass', 'students', 'subject', 'prelim', 'midterm', 'finals'));
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

        ComponentItem::create([
            'component_id' => $component->id,
            'item_name' => $request->item_name,
            'max_score' => $request->max_score,
            'date' => $request->date,
        ]);

        return back()->with('success', 'Item added successfully.');
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

}
