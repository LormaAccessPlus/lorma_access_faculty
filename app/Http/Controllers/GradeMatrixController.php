<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeMatrixController extends Controller
{
    public function zeroBased(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get current academic year and semester
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Only get subjects from the current semester
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->active()
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->with(['studentMappings', 'activities'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('grade-matrix.zero-based', compact('subjects'));
    }

    public function nursing(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get current academic year and semester
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Only get subjects from the current semester
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->active()
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->with(['studentMappings', 'activities'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('grade-matrix.nursing', compact('subjects'));
    }

    public function generalEducation(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get current academic year and semester
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Only get subjects from the current semester
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->active()
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->with(['studentMappings', 'activities'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('grade-matrix.general-education', compact('subjects'));
    }

    public function customized(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get current academic year and semester
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Only get subjects from the current semester
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->active()
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->with(['studentMappings', 'activities'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('grade-matrix.customized', compact('subjects'));
    }

    // Term Grades methods for each matrix type
    public function zeroBasedTermGrades(Subject $subject, string $term = 'prelim'): View
    {
        return $this->termGradesView($subject, $term, 'zero-based');
    }

    public function nursingTermGrades(Subject $subject, string $term = 'prelim'): View
    {
        return $this->termGradesView($subject, $term, 'nursing');
    }

    public function generalEducationTermGrades(Subject $subject, string $term = 'prelim'): View
    {
        return $this->termGradesView($subject, $term, 'general-education');
    }

    public function customizedTermGrades(Subject $subject, string $term = 'prelim'): View
    {
        return $this->termGradesView($subject, $term, 'customized');
    }

    // Full Matrix methods for each matrix type
    public function zeroBasedMatrix(Subject $subject): View
    {
        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by term and type
        $activitiesByTerm = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = \App\Models\GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // DYNAMIC CALCULATION: Recalculate all term grades using Zero-Based formula
        // This ensures grades are calculated based on the matrix type being viewed
        $matrixType = 'zero-based';
        $this->recalculateTermGradesForMatrix($subject, $matrixType);

        // Reload term grades after recalculation
        $termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        return view('grade-matrix.zero-based.matrix', compact(
            'subject',
            'activitiesByTerm',
            'gradeMatrix',
            'termGrades',
            'finalRatingConfig'
        ));
    }

    public function nursingMatrix(Subject $subject): View
    {
        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by term and type
        $activitiesByTerm = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = \App\Models\GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // DYNAMIC CALCULATION: Recalculate all term grades using Nursing formula
        // This ensures grades are calculated based on the matrix type being viewed
        $matrixType = 'nursing';
        $this->recalculateTermGradesForMatrix($subject, $matrixType);

        // Reload term grades after recalculation
        $termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        // Get comprehensive exam scores
        // Check if using JSON storage in subjects table
        $comprehensiveExamScores = $subject->comprehensive_exam_scores ?? [];
        
        // Or if using separate table (uncomment if you prefer this approach)
        // $comprehensiveExams = \App\Models\ComprehensiveExam::where('subject_id', $subject->id)
        //     ->get()
        //     ->keyBy('student_mapping_id');
        
        return view('grade-matrix.nursing.matrix', compact(
            'subject',
            'activitiesByTerm',
            'gradeMatrix',
            'termGrades',
            'matrixType',
            'finalRatingConfig',
            'comprehensiveExamScores'
        ));
    }

    public function generalEducationMatrix(Subject $subject): View
    {
        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by term and type
        $activitiesByTerm = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = \App\Models\GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // DYNAMIC CALCULATION: Recalculate all term grades using General Education formula
        // This ensures grades are calculated based on the matrix type being viewed
        $matrixType = 'general-education';
        $this->recalculateTermGradesForMatrix($subject, $matrixType);

        // Reload term grades after recalculation
        $termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        return view('grade-matrix.general-education.matrix', compact(
            'subject',
            'activitiesByTerm',
            'gradeMatrix',
            'termGrades',
            'finalRatingConfig'
        ));
    }

    public function customizedMatrix(Subject $subject): View
    {
        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by term and type
        $activitiesByTerm = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = \App\Models\GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // DYNAMIC CALCULATION: Recalculate all term grades using Customized formula
        // This ensures grades are calculated based on the matrix type being viewed
        $matrixType = 'customized';
        $this->recalculateTermGradesForMatrix($subject, $matrixType);

        // Reload term grades after recalculation
        $termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        return view('grade-matrix.customized.matrix', compact(
            'subject',
            'activitiesByTerm',
            'gradeMatrix',
            'termGrades',
            'finalRatingConfig'
        ));
    }

    // Helper method for term grades
    private function termGradesView(Subject $subject, string $term, string $matrixType): View
    {
        // Validate term
        $validTerms = ['prelim', 'midterm', 'finals'];
        if (!in_array($term, $validTerms)) {
            abort(404, 'Invalid term');
        }

        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) use ($term) {
                $query->where('term', $term)->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by type for the current term
        $lectureActivities = $subject->activities->where('type', 'lecture');
        $labActivities = $subject->activities->where('type', 'lab');
        
        // Get existing grade records for this term
        $gradeRecords = \App\Models\GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->whereHas('activity', function ($query) use ($term) {
            $query->where('term', $term);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // DYNAMIC CALCULATION: Recalculate all term grades using the specified matrix formula
        // This ensures grades are calculated based on the matrix type being viewed
        $this->recalculateTermGradesForMatrix($subject, $matrixType);

        // Reload term grades after recalculation
        $termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->where('term', $term)
            ->get()
            ->keyBy('student_mapping_id');

        // Calculate progress for each term
        $termProgress = $this->calculateTermProgress($subject);

        // Get grading configuration for this term and matrix type
        $gradingConfig = \App\Models\GradingConfig::where('subject_id', $subject->id)
            ->where('term', $term)
            ->where('matrix_type', $matrixType)
            ->first();

        // If no grading config exists, create one with defaults based on matrix type
        if (!$gradingConfig) {
            $defaults = \App\Models\GradingConfig::getDefaultConfig($term, $matrixType);
            $gradingConfig = \App\Models\GradingConfig::create([
                'subject_id' => $subject->id,
                'term' => $term,
                'matrix_type' => $matrixType,
                'class_standing_weight' => $defaults['class_standing_weight'],
                'exam_weight' => $defaults['exam_weight'],
                'formula_config' => $defaults['formula_config']
            ]);
        }

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        // Get all term grades for all periods
        $allTermGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        return view('grade-matrix.' . $matrixType . '.term-grades', compact(
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
            'matrixType'
        ));
    }

    // Helper method for full matrix
    private function matrixView(Subject $subject, string $matrixType): View
    {
        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get activities grouped by term and type
        $activitiesByTerm = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = \App\Models\GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // DYNAMIC CALCULATION: Recalculate all term grades using the specified matrix formula
        // This ensures grades are calculated based on the matrix type being viewed
        $this->recalculateTermGradesForMatrix($subject, $matrixType);

        // Reload term grades after recalculation
        $termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        return view('grade-matrix.' . $matrixType . '.matrix', compact(
            'subject',
            'activitiesByTerm',
            'gradeMatrix',
            'termGrades',
            'matrixType'
        ));
    }

    // Helper method to calculate term progress
    private function calculateTermProgress(Subject $subject): array
    {
        $terms = ['prelim', 'midterm', 'finals'];
        $progress = [];

        foreach ($terms as $term) {
            $totalStudents = $subject->studentMappings->count();
            $completedStudents = \App\Models\TermGrade::where('subject_id', $subject->id)
                ->where('term', $term)
                ->whereNotNull('term_grade')
                ->count();

            $progress[$term] = [
                'students_with_grades' => $completedStudents,
                'total_students' => $totalStudents,
                'percentage' => $totalStudents > 0 ? round(($completedStudents / $totalStudents) * 100, 1) : 0
            ];
        }

        return $progress;
    }

    /**
     * Recalculate all term grades for a subject using a specific matrix type formula
     * This allows dynamic calculation based on which matrix page is being viewed
     */
    private function recalculateTermGradesForMatrix(Subject $subject, string $matrixType): void
    {
        $gradeController = app(\App\Http\Controllers\GradeController::class);
        $reflection = new \ReflectionClass($gradeController);
        
        // Get all term grades for this subject
        $allTermGrades = \App\Models\TermGrade::where('subject_id', $subject->id)->get();
        
        foreach ($allTermGrades as $termGrade) {
            $studentMapping = \App\Models\StudentMapping::find($termGrade->student_mapping_id);
            if (!$studentMapping) {
                continue;
            }
            
            // Recalculate class standing using the specified matrix type
            // For nursing, this will call recalculateNursingTermGrade which handles everything
            // For other matrix types, this will calculate class standing only
            $recalculateMethod = $reflection->getMethod('recalculateClassStanding');
            $recalculateMethod->setAccessible(true);
            $recalculateMethod->invoke($gradeController, $studentMapping, $termGrade->term, $matrixType);
            
            // For non-nursing matrix types, also recalculate the term grade (including exam grade)
            // For nursing, skip this because recalculateNursingTermGrade already handled it
            if ($matrixType !== 'nursing') {
                $termGrade->refresh(); // Reload from database after class standing recalculation
                $calculateMethod = $reflection->getMethod('calculateTermGrade');
                $calculateMethod->setAccessible(true);
                $calculateMethod->invoke($gradeController, $termGrade, $matrixType);
            }
        }
    }
}
