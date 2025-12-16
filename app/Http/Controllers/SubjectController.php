<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\SubjectService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubjectController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private SubjectService $subjectService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get current semester subjects
        $currentSubjects = $this->subjectService->getCurrentSemesterSubjects($faculty->id);
        
        // Get past semester subjects if requested
        $pastSubjects = [];
        if ($request->has('show_past')) {
            $pastSubjects = $this->subjectService->getPastSemesterSubjects($faculty->id);
        }

        return view('subjects.index', compact('currentSubjects', 'pastSubjects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('subjects.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_subject_id' => 'nullable|integer',
            'subject_code' => 'required|string|max:20',
            'subject_name' => 'required|string|max:255',
            'section' => 'required|string|max:50',
            'type' => ['required', Rule::in(['lecture_only', 'lecture_lab'])],
            'academic_year' => 'required|string|max:20',
            'semester' => 'required|string|max:20',
            'gcr_class_id' => 'nullable|string',
        ]);

        $validated['faculty_id'] = auth()->id();

        $subject = Subject::create($validated);

        return redirect()->route('subjects.show', $subject)
            ->with('success', 'Subject created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Subject $subject): View
    {
        $this->authorize('view', $subject);
        
        $subject->load(['activities', 'studentMappings']);
        
        return view('subjects.show', compact('subject'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subject $subject): View
    {
        $this->authorize('update', $subject);
        
        return view('subjects.edit', compact('subject'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);

        $validated = $request->validate([
            'school_subject_id' => 'nullable|integer',
            'subject_code' => 'required|string|max:20',
            'subject_name' => 'required|string|max:255',
            'section' => 'required|string|max:50',
            'type' => ['required', Rule::in(['lecture_only', 'lecture_lab'])],
            'academic_year' => 'required|string|max:20',
            'semester' => 'required|string|max:20',
            'gcr_class_id' => 'nullable|string',
        ]);

        $subject->update($validated);

        return redirect()->route('subjects.show', $subject)
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);
        
        $subject->delete();

        return redirect()->route('subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }

    /**
     * Sync subjects from school database
     */
    public function sync(Request $request): RedirectResponse
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        $syncedCount = $this->subjectService->syncSubjectsFromSchoolDatabase($faculty->id);

        return redirect()->route('subjects.index')
            ->with('success', "Synced {$syncedCount} subjects from school database.");
    }

    /**
     * Archive a subject
     */
    public function archive(Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);
        
        // Set archived timestamp and state
        $subject->update([
            'gcr_course_state' => 'ARCHIVED',
            'archived_at' => now()
        ]);

        // Preserve computed grades by ensuring all grades have computed_score calculated
        $this->preserveComputedGrades($subject);

        return redirect()->route('archive.show', $subject)
            ->with('success', "Subject '{$subject->subject_code}' has been archived with grades preserved.");
    }
    
    /**
     * Preserve computed grades when archiving
     */
    private function preserveComputedGrades(Subject $subject): void
    {
        try {
            // Get all grading classes for this subject
            $gradingClasses = $subject->gradingClasses()->with(['components.items'])->get();
            
            foreach ($gradingClasses as $gradingClass) {
                foreach ($gradingClass->components as $component) {
                    if ($component->component_type === 'exam') {
                        // Handle exam components
                        $examGrades = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                            ->where('component_id', $component->id)
                            ->whereNotNull('exam_score')
                            ->whereNull('computed_score')
                            ->get();
                        
                        foreach ($examGrades as $grade) {
                            if ($component->formula && $grade->exam_score !== null) {
                                $computedScore = $this->applyFormula(
                                    $grade->exam_score,
                                    $component->exam_max_score ?? 100,
                                    $component->formula
                                );
                                $grade->update(['computed_score' => $computedScore]);
                            }
                        }
                    } else {
                        // Handle regular component items
                        foreach ($component->items as $item) {
                            $grades = \App\Models\StudentGrade::where('component_item_id', $item->id)
                                ->whereNotNull('score')
                                ->whereNull('computed_score')
                                ->get();
                            
                            foreach ($grades as $grade) {
                                if ($component->formula && $grade->score !== null) {
                                    $computedScore = $this->applyFormula(
                                        $grade->score,
                                        $item->max_score,
                                        $component->formula
                                    );
                                    $grade->update(['computed_score' => $computedScore]);
                                }
                            }
                        }
                    }
                }
            }
            
            \Illuminate\Support\Facades\Log::info('Computed grades preserved for archived subject', [
                'subject_id' => $subject->id,
                'subject_code' => $subject->subject_code
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error preserving computed grades during archive', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Apply formula to calculate computed score
     */
    private function applyFormula($score, $total, $formula)
    {
        try {
            // Replace variables in formula
            $expression = str_replace(['score', 'total'], [$score, $total], $formula);
            
            // Safely evaluate the expression
            $result = eval("return {$expression};");
            
            return round($result, 2);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Formula evaluation error during archive', [
                'formula' => $formula,
                'score' => $score,
                'total' => $total,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Unarchive a subject
     */
    public function unarchive(Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);
        
        $subject->update([
            'gcr_course_state' => 'ACTIVE',
            'archived_at' => null
        ]);

        return redirect()->route('subjects.show', $subject)
            ->with('success', "Subject '{$subject->subject_code}' has been unarchived.");
    }
}
