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
                $query->whereNotNull('gcr_student_id')->orderBy('student_name');
            },
            'gradingClasses' => function ($query) {
                $query->with(['components'])->orderByRaw("FIELD(term, 'prelim', 'midterm', 'finals')");
            }
        ]);

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
                    $examMaxScore = $component->exam_max_score ?? 100;
                    $examComputedScore = ($examGrade->exam_score / $examMaxScore) * 100;
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
}
