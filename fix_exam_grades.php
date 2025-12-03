<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TermGrade;
use App\Models\Subject;
use App\Services\MatrixFormulaService;

echo "Fixing exam grades for all term grades...\n\n";

$termGrades = TermGrade::whereNotNull('exam_score')->get();
$fixed = 0;

foreach ($termGrades as $termGrade) {
    $subject = Subject::find($termGrade->subject_id);
    if (!$subject || !$subject->matrix_type) {
        echo "Skipping term grade {$termGrade->id} - no matrix type\n";
        continue;
    }

    // Get the correct formula config
    $formulaConfig = MatrixFormulaService::getFormulaConfig($subject->matrix_type, $termGrade->term);
    
    // Calculate raw exam score
    $rawExamScore = MatrixFormulaService::calculateExamScore(
        $termGrade->exam_score,
        $termGrade->exam_max_score ?? 100,
        $formulaConfig['exam_formula']
    );
    
    // Calculate weighted exam grade
    $examWeight = $formulaConfig['exam_weight'] / 100;
    $weightedExamGrade = $rawExamScore * $examWeight;
    
    // Calculate term grade
    $termGradeValue = ($termGrade->class_standing ?? 0) + $weightedExamGrade;
    
    // Check if it needs updating
    $oldExamGrade = $termGrade->exam_grade;
    $newExamGrade = round($weightedExamGrade, 2);
    
    if (abs($oldExamGrade - $newExamGrade) > 0.01) {
        echo "Fixing term grade {$termGrade->id} (Subject: {$subject->subject_code}, Term: {$termGrade->term})\n";
        echo "  Exam Score: {$termGrade->exam_score}/{$termGrade->exam_max_score}\n";
        echo "  Matrix Type: {$subject->matrix_type}\n";
        echo "  Raw Exam: {$rawExamScore}\n";
        echo "  Exam Weight: " . ($examWeight * 100) . "%\n";
        echo "  Old Exam Grade: {$oldExamGrade}\n";
        echo "  New Exam Grade: {$newExamGrade}\n";
        echo "  Old Term Grade: {$termGrade->term_grade}\n";
        echo "  New Term Grade: " . round($termGradeValue, 2) . "\n\n";
        
        $termGrade->update([
            'exam_grade' => $newExamGrade,
            'term_grade' => round($termGradeValue, 2),
        ]);
        
        $fixed++;
    }
}

echo "\nFixed {$fixed} term grades!\n";
