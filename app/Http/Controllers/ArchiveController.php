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
            ->where('gcr_course_state', 'ARCHIVED')
            ->with(['studentMappings', 'activities'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('archive.index', compact('archivedSubjects'));
    }

    /**
     * Display archived subject details with grades
     */
    public function show(Subject $subject): View
    {
        // Verify subject is archived
        if (!$subject->isArchived()) {
            return redirect()->route('subjects.show', $subject)
                ->with('info', 'This subject is not archived.');
        }

        // Load subject with relationships
        $subject->load([
            'activities' => function ($query) {
                $query->orderBy('term')->orderBy('type')->orderBy('created_at');
            },
            'studentMappings' => function ($query) {
                $query->orderBy('student_name');
            }
        ]);

        // Get all activities grouped by term
        $allActivities = $subject->activities->groupBy('term');
        
        // Get existing grade records for this subject
        $gradeRecords = GradeRecord::whereHas('studentMapping', function ($query) use ($subject) {
            $query->where('subject_id', $subject->id);
        })->with(['studentMapping', 'activity'])->get();

        // Create a matrix structure for easy access
        $gradeMatrix = [];
        foreach ($gradeRecords as $record) {
            $gradeMatrix[$record->student_mapping_id][$record->activity_id] = $record;
        }

        // Get all term grades grouped by student
        $termGrades = TermGrade::where('subject_id', $subject->id)
            ->get()
            ->groupBy('student_mapping_id');

        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];

        return view('archive.show', compact(
            'subject',
            'allActivities',
            'gradeMatrix',
            'termGrades',
            'finalRatingConfig'
        ));
    }
}
