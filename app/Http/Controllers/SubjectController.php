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
        
        $subject->update(['gcr_course_state' => 'ARCHIVED']);

        return redirect()->route('archive.show', $subject)
            ->with('success', "Subject '{$subject->subject_code}' has been archived.");
    }

    /**
     * Unarchive a subject
     */
    public function unarchive(Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);
        
        $subject->update(['gcr_course_state' => 'ACTIVE']);

        return redirect()->route('subjects.show', $subject)
            ->with('success', "Subject '{$subject->subject_code}' has been unarchived.");
    }
}
