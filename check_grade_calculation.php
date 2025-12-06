<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the grading class for NURS101 Prelim
$gradingClass = DB::table('grading_classes')
    ->where('subject_id', 8)
    ->where('term', 'prelim')
    ->first();

echo "Grading Class ID: {$gradingClass->id}\n\n";

// Get Activities component
$component = DB::table('grading_components')
    ->where('grading_class_id', $gradingClass->id)
    ->where('component_name', 'Activities')
    ->first();

echo "Component: {$component->component_name}\n";
echo "Formula: {$component->formula}\n";
echo "Weight: {$component->weight_percentage}%\n\n";

// Get component items
$items = DB::table('component_items')
    ->where('component_id', $component->id)
    ->get();

echo "Component Items:\n";
foreach ($items as $item) {
    echo "  - {$item->item_name}: Max Score = {$item->max_score}\n";
}
echo "\n";

// Get student grades for Leandro
$student = DB::table('student_mappings')
    ->where('subject_id', 8)
    ->first();

if ($student) {
    echo "Student ID: {$student->id}\n\n";
    
    $grades = DB::table('student_grades')
        ->where('grading_class_id', $gradingClass->id)
        ->where('student_mapping_id', $student->id)
        ->get();
    
    echo "Grades:\n";
    $totalComputed = 0;
    $count = 0;
    foreach ($grades as $grade) {
        $item = DB::table('component_items')->where('id', $grade->component_item_id)->first();
        echo "  - {$item->item_name}: Score = {$grade->score}, Computed = {$grade->computed_score}\n";
        
        // Manual calculation
        $manual = ($grade->score / $item->max_score) * 60 + 40;
        echo "    Manual calc: ({$grade->score}/{$item->max_score}) * 60 + 40 = {$manual}\n";
        
        if ($grade->computed_score !== null) {
            $totalComputed += $grade->computed_score;
            $count++;
        }
    }
    
    if ($count > 0) {
        $average = $totalComputed / $count;
        echo "\nComponent Grade (Average): {$totalComputed} / {$count} = {$average}\n";
    }
}
