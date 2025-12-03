<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\StudentMapping;
use App\Services\StudentMappingService;
use App\Services\GoogleClassroomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MappingController extends Controller
{
    public function __construct(
        private StudentMappingService $mappingService,
        private GoogleClassroomService $classroomService
    ) {}

    /**
     * Display the mapping interface for a subject
     */
    public function index(Subject $subject): View
    {
        $mappings = $this->mappingService->getMappingsForSubject($subject);
        $conflicts = $this->mappingService->getMappingConflicts($subject);
        
        return view('mappings.index', compact('subject', 'mappings', 'conflicts'));
    }

    /**
     * Show the automatic mapping results
     */
    public function autoMatch(Subject $subject): View|RedirectResponse
    {
        try {
            // Fetch GCR students if the subject has a connected classroom
            if (!$subject->gcr_class_id) {
                return redirect()->route('mappings.index', $subject)
                    ->with('error', 'No Google Classroom connected to this subject.');
            }

            // Get the authenticated faculty member
            $faculty = request()->attributes->get('faculty') ?? auth('faculty')->user();
            if (!$faculty) {
                return redirect()->route('mappings.index', $subject)
                    ->with('error', 'You must be logged in to access Google Classroom.');
            }

            // Authenticate with Google Classroom
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                return redirect()->route('mappings.index', $subject)
                    ->with('error', 'Failed to authenticate with Google Classroom. Please log in again.');
            }

            $gcrStudents = $this->classroomService->getStudents($subject->gcr_class_id);
            $results = $this->mappingService->autoMatchStudents($subject, $gcrStudents);
            
            return view('mappings.auto-match', compact('subject', 'results'));
        } catch (\Exception $e) {
            return redirect()->route('mappings.index', $subject)
                ->with('error', 'Failed to fetch students from Google Classroom: ' . $e->getMessage());
        }
    }

    /**
     * Save automatic matching results
     */
    public function saveAutoMatches(Request $request, Subject $subject): RedirectResponse
    {
        $request->validate([
            'matches' => 'required|array',
            'matches.*.gcr_student' => 'required|array',
            'matches.*.school_student' => 'required|array',
            'matches.*.confidence' => 'required|numeric|min:0|max:1',
        ]);

        try {
            $this->mappingService->saveMatches($subject, $request->matches);
            
            return redirect()->route('mappings.index', $subject)
                ->with('success', 'Automatic matches saved successfully.');
        } catch (\Exception $e) {
            return redirect()->route('mappings.auto-match', $subject)
                ->with('error', 'Failed to save matches: ' . $e->getMessage());
        }
    }

    /**
     * Create or update a manual mapping
     */
    public function store(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'gcr_student' => 'required|array',
            'gcr_student.userId' => 'required|string',
            'gcr_student.profile.name.fullName' => 'required|string',
            'gcr_student.emailAddress' => 'nullable|email',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        try {
            $mapping = $this->mappingService->createManualMapping(
                $subject,
                $request->gcr_student,
                $request->student_id
            );

            return response()->json([
                'success' => true,
                'mapping' => $mapping,
                'message' => 'Mapping created successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing mapping
     */
    public function update(Request $request, Subject $subject, StudentMapping $mapping): JsonResponse
    {
        $request->validate([
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        try {
            $mapping->update([
                'student_id' => $request->student_id,
                'mapping_confidence' => $request->student_id ? 1.0 : 0.0,
            ]);

            return response()->json([
                'success' => true,
                'mapping' => $mapping->fresh(),
                'message' => 'Mapping updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a mapping
     */
    public function destroy(Subject $subject, StudentMapping $mapping): JsonResponse
    {
        try {
            $this->mappingService->deleteMapping($mapping->id);

            return response()->json([
                'success' => true,
                'message' => 'Mapping deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show conflict resolution interface
     */
    public function conflicts(Subject $subject): View
    {
        $conflicts = $this->mappingService->getMappingConflicts($subject);
        
        return view('mappings.conflicts', compact('subject', 'conflicts'));
    }

    /**
     * Resolve a mapping conflict
     */
    public function resolveConflict(Request $request, Subject $subject): JsonResponse
    {
        $request->validate([
            'keep_mapping_id' => 'required|integer|exists:student_mappings,id',
            'remove_mapping_ids' => 'required|array',
            'remove_mapping_ids.*' => 'integer|exists:student_mappings,id',
        ]);

        try {
            $success = $this->mappingService->resolveConflict(
                $request->keep_mapping_id,
                $request->remove_mapping_ids
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conflict resolved successfully.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resolve conflict.'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve conflict: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available students for mapping (AJAX endpoint)
     */
    public function getSchoolStudents(Subject $subject): JsonResponse
    {
        try {
            // Get students enrolled in this subject
            $students = $subject->students()
                ->where('student_subject.status', 'enrolled')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'full_name' => $student->full_name,
                        'email' => $student->email,
                        'student_number' => $student->student_number,
                    ];
                });

            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch students: ' . $e->getMessage()
            ], 500);
        }
    }
}
