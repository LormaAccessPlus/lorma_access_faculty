<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TermGrade;
use App\Models\GradeRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    /**
     * Display list of archived subjects
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get archived subjects for this faculty
        $archivedSubjects = Subject::where('faculty_id', $faculty->id)
            ->archived()
            ->with(['studentMappings', 'activities', 'gradingClasses'])
            ->orderBy('archived_at', 'desc')
            ->get();

        return view('archive.index', compact('archivedSubjects'));
    }

    /**
     * Display archived subject details with grades
     */
    public function show($id): View
    {
        $faculty = auth('faculty')->user();
        $subject = Subject::where('id', $id)
            ->where('faculty_id', $faculty->id)
            ->archived()
            ->firstOrFail();
        
        // Verify subject is archived
        if (!$subject->archived_at) {
            return redirect()->route('dashboard')
                ->with('info', 'This subject is not archived.');
        }

        // Load subject with relationships (only matched students)
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->whereNotNull('gcr_student_id');
            },
            'gradingClasses' => function ($query) {
                $query->with(['components'])->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')");
            }
        ]);

        // Sort students by gender (M first, then F) and then by name
        $subject->studentMappings = $subject->studentMappings->sortBy(function ($student) {
            $csvData = $student->csv_data ?? [];
            $gender = strtoupper($csvData['gender'] ?? 'Z'); // Default 'Z' for unknown gender to sort last
            $name = $student->student_name;
            
            // Sort by gender (M first, then F, then others), then by name
            $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
            return $genderOrder . '_' . $name;
        })->values();

        // Get grading classes for this subject
        $gradingClasses = $subject->gradingClasses;
        
        // Get final rating formula
        $savedFormula = $subject->final_rating_formula;
        if ($savedFormula && isset($savedFormula['components'])) {
            $finalRatingFormula = $savedFormula['components'];
            $termWeights = $savedFormula['term_weights'];
        } else {
            $finalRatingFormula = $savedFormula ?? ['final_grade' => 100];
            $termWeights = ['prelim' => 30, 'midterm' => 30, 'finals' => 40];
        }
        
        // Calculate term grades for each student
        $termGrades = [];
        foreach ($subject->studentMappings as $student) {
            foreach ($gradingClasses as $gradingClass) {
                $termGrade = $this->calculateTermGrade($student, $gradingClass);
                $termGrades[$student->id][$gradingClass->term] = $termGrade;
            }
        }
        
        // Get all activities grouped by term
        $allActivities = $subject->activities->groupBy('term');

        return view('archive.show', compact(
            'subject',
            'gradingClasses',
            'termGrades',
            'termWeights',
            'finalRatingFormula',
            'allActivities'
        ));
    }
    
    private function calculateTermGrade($student, $gradingClass)
    {
        $termGrade = 0;
        $totalWeight = 0;
        
        foreach($gradingClass->components as $component) {
            if ($component->component_name === 'Exam') {
                $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
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
                $grades = \App\Models\StudentGrade::where('student_mapping_id', $student->id)
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

    /**
     * Export archived subject full matrix to PDF
     */
    public function exportFullMatrixPDF(Request $request, $id)
    {
        $faculty = auth('faculty')->user();
        $subject = Subject::where('id', $id)
            ->where('faculty_id', $faculty->id)
            ->archived()
            ->firstOrFail();

        // Load subject with relationships (only matched students)
        $subject->load([
            'studentMappings' => function ($query) {
                $query->whereNotNull('gcr_student_id');
            },
            'gradingClasses' => function ($query) {
                $query->with(['components' => function($q) {
                    $q->with('items');
                }])->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')");
            }
        ]);

        // Sort students by gender (M first, then F) and then by name
        $subject->studentMappings = $subject->studentMappings->sortBy(function ($student) {
            $csvData = $student->csv_data ?? [];
            $gender = strtoupper($csvData['gender'] ?? 'Z');
            $name = $student->student_name;
            
            $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
            return $genderOrder . '_' . $name;
        })->values();

        // Get grading classes
        $gradingClasses = $subject->gradingClasses;

        // Get dean name from request
        $deanName = $request->input('dean_name', '');
        $adviserName = $faculty ? $faculty->name : '';

        try {
            // Log debug info
            \Log::info('Exporting archived PDF', [
                'subject_id' => $subject->id,
                'students_count' => $subject->studentMappings->count(),
                'grading_classes_count' => $gradingClasses->count(),
                'dean_name' => $deanName,
                'adviser_name' => $adviserName
            ]);
            
            $pdf = \PDF::loadView('grades.exports.archived-full-matrix', compact(
                'subject',
                'gradingClasses',
                'deanName',
                'adviserName'
            ));
            
            $pdf->setPaper('legal', 'landscape');
            
            return $pdf->download($subject->subject_code . '_Full_Matrix.pdf');
        } catch (\Exception $e) {
            \Log::error('Failed to export archived PDF', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export archived subject term grades to PDF
     */
    public function exportTermGradesPDF(Request $request, $id, $term)
    {
        $faculty = auth('faculty')->user();
        $subject = Subject::where('id', $id)
            ->where('faculty_id', $faculty->id)
            ->archived()
            ->firstOrFail();

        // Validate term
        $validTerms = ['prelim', 'midterm', 'finals'];
        if (!in_array($term, $validTerms)) {
            abort(404, 'Invalid term');
        }

        // Load subject with relationships (only matched students)
        $subject->load([
            'studentMappings' => function ($query) {
                $query->whereNotNull('gcr_student_id');
            },
            'gradingClasses' => function ($query) {
                $query->with(['components' => function($q) {
                    $q->with('items');
                }])->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')");
            }
        ]);

        // Sort students by gender (M first, then F) and then by name
        $subject->studentMappings = $subject->studentMappings->sortBy(function ($student) {
            $csvData = $student->csv_data ?? [];
            $gender = strtoupper($csvData['gender'] ?? 'Z');
            $name = $student->student_name;
            
            $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
            return $genderOrder . '_' . $name;
        })->values();

        // Get grading classes
        $gradingClasses = $subject->gradingClasses;

        // Get dean name from request
        $deanName = $request->input('dean_name', '');
        $adviserName = $faculty ? $faculty->name : '';

        try {
            \Log::info('Exporting archived term PDF', [
                'subject_id' => $subject->id,
                'term' => $term,
                'students_count' => $subject->studentMappings->count(),
                'dean_name' => $deanName,
                'adviser_name' => $adviserName
            ]);
            
            $pdf = \PDF::loadView('grades.exports.archived-term-grades', compact(
                'subject',
                'gradingClasses',
                'term',
                'deanName',
                'adviserName'
            ));
            
            $pdf->setPaper('legal', 'landscape');
            
            return $pdf->download($subject->subject_code . '_' . ucfirst($term) . '_Grades.pdf');
        } catch (\Exception $e) {
            \Log::error('Failed to export archived term PDF', [
                'subject_id' => $subject->id,
                'term' => $term,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Sync archived subjects from Google Classroom
     */
    public function sync(Request $request)
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        try {
            // Get all active subjects with GCR connections for this faculty
            $subjects = Subject::where('faculty_id', $faculty->id)
                ->whereNotNull('gcr_class_id')
                ->whereNull('archived_at')
                ->get();

            if ($subjects->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'archived_count' => 0,
                    'message' => 'No connected subjects found to check.'
                ]);
            }

            $classroomService = app(\App\Services\GoogleClassroomService::class);
            
            // Try to authenticate with faculty's Google account
            if (!$classroomService->authenticateWithFaculty($faculty)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not authenticate with Google Classroom. Please check your connection.'
                ]);
            }

            $archivedCount = 0;
            foreach ($subjects as $subject) {
                if ($classroomService->checkAndArchiveSubject($subject)) {
                    $archivedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'archived_count' => $archivedCount,
                'message' => $archivedCount > 0 
                    ? "Successfully archived {$archivedCount} subject(s)."
                    : 'No new archived subjects found.'
            ]);

        } catch (\Exception $e) {
            Log::error('Archive sync failed', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
