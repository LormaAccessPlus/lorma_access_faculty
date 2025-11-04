<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\SubjectMappingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SubjectMappingController extends Controller
{
    private SubjectMappingService $subjectMappingService;

    public function __construct(SubjectMappingService $subjectMappingService)
    {
        $this->subjectMappingService = $subjectMappingService;
    }

    /**
     * Display the subject mapping interface
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? Auth::guard('faculty')->user();
        
        // Temporary: Use faculty ID 1 for testing if not authenticated
        if (!$faculty) {
            $faculty = \App\Models\Faculty::find(1);
            if (!$faculty) {
                abort(401, 'Faculty authentication required');
            }
        }
        
        // Get current academic year and semester (you may want to make this configurable)
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Get available courses and subjects for mapping
        $availableGcrCourses = $this->subjectMappingService->getAvailableGoogleClassroomCourses($faculty->id);
        $availableSchoolSubjects = $this->subjectMappingService->getAvailableSchoolSubjects(
            $faculty,
            $currentAcademicYear,
            $currentSemester
        );
        
        // Get existing mappings
        $existingMappings = Subject::where('faculty_id', $faculty->id)
            ->where('type', 'mapped')
            ->with('studentMappings')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get mapping statistics
        $stats = $this->subjectMappingService->getMappingStatistics($faculty->id);
        
        return view('subjects.mapping.index', compact(
            'availableGcrCourses',
            'availableSchoolSubjects',
            'existingMappings',
            'stats',
            'currentAcademicYear',
            'currentSemester'
        ));
    }

    /**
     * Create a new subject mapping
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'gcr_class_id' => 'required|string',
            'school_subject_code' => 'required|string',
            'academic_year' => 'required|string',
            'semester' => 'required|string',
            'notes' => 'nullable|string|max:500'
        ]);

        $faculty = $request->attributes->get('faculty') ?? Auth::guard('faculty')->user();
        
        // Temporary: Use faculty ID 1 for testing if not authenticated
        if (!$faculty) {
            $faculty = \App\Models\Faculty::find(1);
            if (!$faculty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faculty authentication required'
                ], 401);
            }
        }
        
        $mappingData = [
            'faculty_id' => $faculty->id,
            'gcr_class_id' => $request->gcr_class_id,
            'school_subject_code' => $request->school_subject_code,
            'academic_year' => $request->academic_year,
            'semester' => $request->semester,
            'notes' => $request->notes
        ];
        
        $result = $this->subjectMappingService->createSubjectMapping($mappingData);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'subject' => $result['subject'],
                'redirect_url' => route('subjects.show', $result['subject'])
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 422);
        }
    }

    /**
     * Get available Google Classroom courses via AJAX
     */
    public function getGoogleClassroomCourses(Request $request): JsonResponse
    {
        $faculty = $request->attributes->get('faculty') ?? Auth::guard('faculty')->user();
        
        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty authentication required'
            ], 401);
        }
        
        $courses = $this->subjectMappingService->getAvailableGoogleClassroomCourses($faculty->id);
        
        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }

    /**
     * Get available school subjects via AJAX
     */
    public function getSchoolSubjects(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'required|string'
        ]);

        $faculty = $request->attributes->get('faculty') ?? Auth::guard('faculty')->user();
        
        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty authentication required'
            ], 401);
        }
        
        $subjects = $this->subjectMappingService->getAvailableSchoolSubjects(
            $faculty,
            $request->academic_year,
            $request->semester
        );
        
        return response()->json([
            'success' => true,
            'subjects' => $subjects
        ]);
    }

    /**
     * Delete a subject mapping
     */
    public function destroy(Request $request, Subject $subject): JsonResponse
    {
        // Ensure the subject belongs to the authenticated faculty
        $faculty = $request->attributes->get('faculty') ?? Auth::guard('faculty')->user();
        
        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty authentication required'
            ], 401);
        }
        
        if ($subject->faculty_id !== $faculty->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this subject'
            ], 403);
        }
        
        try {
            // Delete student mappings first
            $subject->studentMappings()->delete();
            
            // Delete the subject
            $subject->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Subject mapping deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mapping statistics for dashboard
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $faculty = $request->attributes->get('faculty') ?? Auth::guard('faculty')->user();
        
        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty authentication required'
            ], 401);
        }
        
        $stats = $this->subjectMappingService->getMappingStatistics($faculty->id);
        
        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }
}