<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradingClass;
use App\Models\GradingComponent;
use App\Models\ComponentItem;
use App\Models\StudentGrade;
use App\Models\StudentMapping;
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
            'term' => 'required|in:prelim,midterm,finals'
        ]);

        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        $subject = Subject::where('id', $request->subject_id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        // Check if class already exists for this term
        $existing = GradingClass::where('subject_id', $subject->id)
            ->where('term', $request->term)
            ->first();

        if ($existing) {
            return back()->with('error', 'This class already exists for the selected term.');
        }

        $gradingClass = GradingClass::create([
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
            'gcr_class_id' => $subject->gcr_class_id,
            'class_name' => $subject->subject_name . ' - ' . $subject->section,
            'term' => $request->term,
            'term_formula' => null, // Will be set during configuration
        ]);

        return redirect()->route('grading.configure', $gradingClass->id)
            ->with('success', 'Class added successfully. Configure your grading components.');
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
            // Delete existing components
            $gradingClass->components()->delete();

            // Create new components
            foreach ($request->components as $index => $component) {
                GradingComponent::create([
                    'grading_class_id' => $gradingClass->id,
                    'component_name' => $component['name'],
                    'component_type' => $component['type'],
                    'weight_percentage' => $component['weight'],
                    'formula' => $component['formula'] ?? null,
                    'order' => $index,
                ]);
            }

            // Save term formula
            $gradingClass->update([
                'term_formula' => $request->term_formula ? ['formula' => $request->term_formula] : null,
            ]);

            DB::commit();
            return redirect()->route('grading.grade-sheet', $gradingClass->id)
                ->with('success', 'Configuration saved successfully.');

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
            ->with(['subject.studentMappings', 'components.items.grades'])
            ->firstOrFail();

        // Get students for this class
        $students = StudentMapping::where('subject_id', $gradingClass->subject_id)
            ->where('auto_matched', true)
            ->orderBy('student_name')
            ->get();

        return view('grading.grade-sheet', compact('gradingClass', 'students'));
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

        return response()->json([
            'success' => true,
            'computed_score' => $computedScore,
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
}
