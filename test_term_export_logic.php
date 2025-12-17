<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;
use App\Models\StudentGrade;

echo "Testing Term-Specific Export Logic for GS101\n";
echo "===========================================\n\n";

// Get GS101 subject
$subject = Subject::where('subject_code', 'GS101')->first();
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

// Sort students by gender
$subject->studentMappings = $subject->studentMappings->sortBy(function ($student) {
    $csvData = $student->csv_data ?? [];
    $gender = strtoupper($csvData['gender'] ?? 'Z');
    $name = $student->student_name;
    
    $genderOrder = $gender === 'M' ? '1' : ($gender === 'F' ? '2' : '3');
    return $genderOrder . '_' . $name;
})->values();

$gradingClasses = $subject->gradingClasses;
$term = 'prelim'; // Test with prelim term

echo "📊 Prelim Term Export Data (Detailed Breakdown):\n";
echo str_pad("#", 3) . str_pad("Student", 25) . str_pad("Gender", 8) . str_pad("Activities", 12) . str_pad("Quizzes", 12) . str_pad("Exam", 12) . str_pad("Raw", 8) . str_pad("Computed", 10) . str_pad("Term", 8) . "Status\n";
echo str_repeat("-", 105) . "\n";

$rowNumber = 1;
foreach ($subject->studentMappings as $studentMapping) {
    $csvData = $studentMapping->csv_data ?? [];
    
    // Get the specific term grading class
    $termGradingClass = $gradingClasses->where('term', $term)->first();
    $termGrade = 0;
    $activitiesGrade = null;
    $quizzesGrade = null;
    $examGrade = null;
    $rawGrade = null;
    $computedGrade = 0;
    
    if ($termGradingClass) {
        foreach($termGradingClass->components as $component) {
            if ($component->component_name === 'Activities') {
                $grades = StudentGrade::where('student_mapping_id', $studentMapping->id)
                    ->whereIn('component_item_id', $component->items->pluck('id'))
                    ->whereNotNull('computed_score')
                    ->get();
                
                if ($grades->count() > 0) {
                    $activitiesGrade = $grades->avg('computed_score');
                    $termGrade += $activitiesGrade * ($component->weight_percentage / 100);
                }
            }
            elseif ($component->component_name === 'Quizzes') {
                $grades = StudentGrade::where('student_mapping_id', $studentMapping->id)
                    ->whereIn('component_item_id', $component->items->pluck('id'))
                    ->whereNotNull('computed_score')
                    ->get();
                
                if ($grades->count() > 0) {
                    $quizzesGrade = $grades->avg('computed_score');
                    $termGrade += $quizzesGrade * ($component->weight_percentage / 100);
                }
            }
            elseif ($component->component_name === 'Exam') {
                $examGradeRecord = StudentGrade::where('grading_class_id', $termGradingClass->id)
                    ->where('student_mapping_id', $studentMapping->id)
                    ->where('component_id', $component->id)
                    ->first();
                
                if ($examGradeRecord && $examGradeRecord->exam_score !== null) {
                    $rawGrade = $examGradeRecord->exam_score;
                    if ($examGradeRecord->computed_score !== null) {
                        $examGrade = $examGradeRecord->computed_score;
                    } else {
                        $examMaxScore = $component->exam_max_score ?? 100;
                        $examGrade = ($examGradeRecord->exam_score / $examMaxScore) * 100;
                    }
                    $termGrade += $examGrade * ($component->weight_percentage / 100);
                }
            }
        }
    }
    
    $computedGrade = $termGrade;
    $status = $computedGrade >= 75 ? 'Passed' : ($computedGrade > 0 ? 'Failed' : '');
    
    echo str_pad($rowNumber++, 3) . 
         str_pad(substr($studentMapping->student_name, 0, 24), 25) . 
         str_pad($csvData['gender'] ?? '', 8) . 
         str_pad($activitiesGrade !== null ? number_format($activitiesGrade, 2) : '', 12) . 
         str_pad($quizzesGrade !== null ? number_format($quizzesGrade, 2) : '', 12) . 
         str_pad($examGrade !== null ? number_format($examGrade, 2) : '', 12) . 
         str_pad($rawGrade !== null ? number_format($rawGrade, 2) : '', 8) . 
         str_pad($computedGrade > 0 ? number_format($computedGrade, 2) : '', 10) . 
         str_pad($computedGrade > 0 ? number_format($computedGrade, 2) : '', 8) . 
         $status . "\n";
}

echo "\n✅ This is what should appear in the Prelim Term PDF!\n";
echo "   Shows detailed breakdown: Activities, Quizzes, Exam, Raw, Computed, Term Grade.\n";
echo "   Different from Full Matrix which only shows term grades.\n\n";

echo "📋 Export Types Summary:\n";
echo "   • Full Matrix Export: Only Prelim, Midterm, Finals, Final Grade\n";
echo "   • Term Export (Prelim): Activities, Quizzes, Exam, Raw, Computed, Prelim Grade\n";
echo "   • Term Export (Midterm): Activities, Quizzes, Exam, Raw, Computed, Midterm Grade\n";
echo "   • Term Export (Finals): Activities, Quizzes, Exam, Raw, Computed, Finals Grade\n";