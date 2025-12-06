<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Remove duplicate "Activities" components from all terms
$duplicates = [23]; // Finals

foreach ($duplicates as $componentId) {
    $component = DB::table('grading_components')->where('id', $componentId)->first();
    
    if (!$component) {
        echo "Component ID {$componentId} not found\n";
        continue;
    }
    
    $gradingClass = DB::table('grading_classes')->where('id', $component->grading_class_id)->first();
    
    echo "Removing duplicate from {$gradingClass->term}:\n";
    echo "  Component: {$component->component_name} ({$component->component_type})\n";
    echo "  Weight: {$component->weight_percentage}%\n";
    
    // Delete component items and grades
    $items = DB::table('component_items')->where('component_id', $componentId)->get();
    foreach ($items as $item) {
        DB::table('student_grades')->where('component_item_id', $item->id)->delete();
    }
    DB::table('component_items')->where('component_id', $componentId)->delete();
    
    // Delete the component
    DB::table('grading_components')->where('id', $componentId)->delete();
    echo "  ✓ Deleted\n\n";
}

// Show summary for all terms
echo "=== Summary ===\n";
$gradingClasses = DB::table('grading_classes')->where('subject_id', 9)->get();
foreach ($gradingClasses as $class) {
    echo "\n{$class->term}:\n";
    $components = DB::table('grading_components')->where('grading_class_id', $class->id)->get();
    $total = 0;
    foreach ($components as $comp) {
        echo "  - {$comp->component_name}: {$comp->weight_percentage}%\n";
        $total += $comp->weight_percentage;
    }
    echo "  Total: {$total}%\n";
}
