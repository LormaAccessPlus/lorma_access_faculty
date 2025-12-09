<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    /**
     * Display a listing of activities.
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        if (!$faculty) {
            abort(401, 'Faculty authentication required');
        }

        $subjectId = $request->get('subject_id');
        $type = $request->get('type');
        $term = $request->get('term');

        // Get current academic year and semester
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');

        // Get faculty's subjects for the filter dropdown (current semester only)
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->active()
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->orderBy('subject_code')
            ->get();

        $query = Activity::with('subject')
            ->whereHas('subject', function($q) use ($faculty, $currentAcademicYear, $currentSemester) {
                $q->where('faculty_id', $faculty->id)
                    ->where('academic_year', $currentAcademicYear)
                    ->where('semester', $currentSemester);
            })
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($term, fn($q) => $q->where('term', $term))
            ->orderBy('term')
            ->orderBy('type')
            ->orderBy('created_at');

        $activities = $query->get();
        $selectedSubject = $subjectId ? Subject::find($subjectId) : null;

        // Group activities by term and type for better organization
        $organizedActivities = [
            'prelim' => [
                'lecture' => $activities->where('term', 'prelim')->where('type', 'lecture'),
                'lab' => $activities->where('term', 'prelim')->where('type', 'lab')
            ],
            'midterm' => [
                'lecture' => $activities->where('term', 'midterm')->where('type', 'lecture'),
                'lab' => $activities->where('term', 'midterm')->where('type', 'lab')
            ],
            'finals' => [
                'lecture' => $activities->where('term', 'finals')->where('type', 'lecture'),
                'lab' => $activities->where('term', 'finals')->where('type', 'lab')
            ]
        ];

        return view('activities.index', compact(
            'activities',
            'organizedActivities',
            'subjects',
            'selectedSubject',
            'subjectId',
            'type',
            'term'
        ));
    }

    /**
     * Show the form for creating a new activity.
     */
    public function create(Request $request): View
    {
        $subjectId = $request->get('subject_id');
        $subject = Subject::findOrFail($subjectId);
        
        return view('activities.create', compact('subject'));
    }

    /**
     * Store a newly created activity.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['lecture', 'lab'])],
            'term' => ['required', Rule::in(['prelim', 'midterm', 'finals'])],
            'max_score' => 'required|numeric|min:0|max:999999.99',
            'weight' => 'nullable|numeric|min:0|max:100',
            'activity_category' => ['nullable', Rule::in(['activity', 'quiz'])],
            'gcr_assignment_id' => 'nullable|string|max:255'
        ]);
        
        // Set default activity_category if not provided
        if (!isset($validated['activity_category'])) {
            $validated['activity_category'] = 'activity';
        }

        // Verify subject belongs to authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        $subject = Subject::findOrFail($validated['subject_id']);
        if ($subject->faculty_id !== $faculty->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activity = Activity::create($validated);

        // Automatically create grading item in the corresponding component
        $this->syncActivityToGradingItem($activity);

        return response()->json([
            'message' => 'Activity created successfully',
            'activity' => $activity->load('subject')
        ], 201);
    }

    /**
     * Display the specified activity.
     */
    public function show(Activity $activity, Request $request): JsonResponse
    {
        // Verify activity belongs to authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        if ($activity->subject->faculty_id !== $faculty->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'activity' => $activity->load('subject')
        ]);
    }

    /**
     * Show the form for editing the specified activity.
     */
    public function edit(Activity $activity, Request $request): View
    {
        // Verify activity belongs to authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        if ($activity->subject->faculty_id !== $faculty->id) {
            abort(403);
        }

        return view('activities.edit', compact('activity'));
    }

    /**
     * Update the specified activity.
     */
    public function update(Request $request, Activity $activity): JsonResponse
    {
        // Verify activity belongs to authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        if ($activity->subject->faculty_id !== $faculty->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['lecture', 'lab'])],
            'term' => ['required', Rule::in(['prelim', 'midterm', 'finals'])],
            'max_score' => 'required|numeric|min:0|max:999999.99',
            'weight' => 'nullable|numeric|min:0|max:100',
            'gcr_assignment_id' => 'nullable|string|max:255'
        ]);

        $activity->update($validated);

        // Update corresponding grading item if it exists
        $this->updateGradingItem($activity);

        return response()->json([
            'message' => 'Activity updated successfully',
            'activity' => $activity->load('subject')
        ]);
    }

    /**
     * Remove the specified activity.
     */
    public function destroy(Activity $activity, Request $request): JsonResponse
    {
        // Verify activity belongs to authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        if ($activity->subject->faculty_id !== $faculty->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully'
        ]);
    }

    /**
     * Get activities organized by type and term for a subject.
     */
    public function getOrganized(Subject $subject, Request $request): JsonResponse
    {
        // Verify subject belongs to authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        if ($subject->faculty_id !== $faculty->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activities = $subject->activities()
            ->orderBy('term')
            ->orderBy('type')
            ->orderBy('created_at')
            ->get();

        $organized = [
            'prelim' => [
                'lecture' => $activities->where('term', 'prelim')->where('type', 'lecture')->values(),
                'lab' => $activities->where('term', 'prelim')->where('type', 'lab')->values()
            ],
            'midterm' => [
                'lecture' => $activities->where('term', 'midterm')->where('type', 'lecture')->values(),
                'lab' => $activities->where('term', 'midterm')->where('type', 'lab')->values()
            ],
            'finals' => [
                'lecture' => $activities->where('term', 'finals')->where('type', 'lecture')->values(),
                'lab' => $activities->where('term', 'finals')->where('type', 'lab')->values()
            ]
        ];

        return response()->json([
            'subject' => $subject,
            'activities' => $organized
        ]);
    }

    /**
     * Bulk create activities from Google Classroom.
     */
    public function bulkCreateFromGCR(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'activities' => 'required|array',
            'activities.*.gcr_assignment_id' => 'required|string',
            'activities.*.name' => 'required|string|max:255',
            'activities.*.type' => ['required', Rule::in(['lecture', 'lab'])],
            'activities.*.term' => ['required', Rule::in(['prelim', 'midterm', 'finals'])],
            'activities.*.max_score' => 'required|numeric|min:0|max:999999.99',
            'activities.*.weight' => 'nullable|numeric|min:0|max:100'
        ]);

        // Verify subject belongs to authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        $subject = Subject::findOrFail($validated['subject_id']);
        if ($subject->faculty_id !== $faculty->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $createdActivities = [];
        foreach ($validated['activities'] as $activityData) {
            $activityData['subject_id'] = $validated['subject_id'];
            
            // Check if activity with same GCR assignment ID already exists
            $existing = Activity::where('subject_id', $validated['subject_id'])
                ->where('gcr_assignment_id', $activityData['gcr_assignment_id'])
                ->first();
                
            if (!$existing) {
                $createdActivities[] = Activity::create($activityData);
            }
        }

        return response()->json([
            'message' => count($createdActivities) . ' activities created successfully',
            'activities' => $createdActivities
        ], 201);
    }

    /**
     * Sync activity to grading item
     */
    private function syncActivityToGradingItem(Activity $activity)
    {
        \Log::info('Syncing activity to grading item', ['activity_id' => $activity->id, 'name' => $activity->name]);
        
        // Find the grading class for this subject and term
        $gradingClass = \App\Models\GradingClass::where('subject_id', $activity->subject_id)
            ->where('term', $activity->term)
            ->first();

        if (!$gradingClass) {
            \Log::warning('No grading class found', ['subject_id' => $activity->subject_id, 'term' => $activity->term]);
            return;
        }

        // First, check if there's a "Class Standing" component (which combines activities and quizzes)
        $component = $gradingClass->components()
            ->where('component_type', 'class_standing')
            ->first();

        // If no Class Standing component, look for specific component by name
        if (!$component) {
            // Map activity category to component name
            $componentName = match($activity->activity_category) {
                'quiz' => 'Quizzes',
                'activity' => 'Activities',
                default => 'Activities'
            };

            // Find the component by name
            $component = $gradingClass->components()
                ->where('component_name', $componentName)
                ->first();
        }

        if (!$component) {
            \Log::warning('No component found', [
                'subject_id' => $activity->subject_id, 
                'term' => $activity->term,
                'activity_category' => $activity->activity_category
            ]);
            return;
        }

        // Check if already linked to avoid duplicates
        $existingItem = \App\Models\ComponentItem::where('component_id', $component->id)
            ->where('activity_id', $activity->id)
            ->first();

        if ($existingItem) {
            \Log::info('Activity already linked to component', ['activity_id' => $activity->id, 'component_id' => $component->id]);
            return;
        }

        // Create the grading item
        $item = \App\Models\ComponentItem::create([
            'component_id' => $component->id,
            'item_name' => $activity->name,
            'max_score' => $activity->max_score,
            'date' => now(),
            'activity_id' => $activity->id
        ]);
        
        \Log::info('Created component item', ['item_id' => $item->id, 'activity_id' => $item->activity_id, 'component_type' => $component->component_type]);
    }

    /**
     * Update grading item when activity is updated
     */
    private function updateGradingItem(Activity $activity)
    {
        // Find grading item linked to this activity
        $gradingItem = \App\Models\ComponentItem::where('activity_id', $activity->id)->first();

        if ($gradingItem) {
            $gradingItem->update([
                'item_name' => $activity->name,
                'max_score' => $activity->max_score,
            ]);
        }
    }
}
