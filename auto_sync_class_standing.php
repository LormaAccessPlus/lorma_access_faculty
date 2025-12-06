<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Auto-Sync Activities/Quizzes to Class Standing Components ===\n\n";

// Find all class_standing components
$classStandingComponents = DB::table('grading_components')
    ->where('component_type', 'class_standing')
    ->get();

echo "Found " . $classStandingComponents->count() . " Class Standing components\n\n";

foreach ($classStandingComponents as $component) {
    $gradingClass = DB::table('grading_classes')->where('id', $component->grading_class_id)->first();
    $subject = DB::table('subjects')->where('id', $gradingClass->subject_id)->first();
    
    echo "Processing: {$subject->subject_code} - {$gradingClass->term}\n";
    echo "  Component: {$component->component_name} (ID: {$component->id})\n";
    
    // Get all activities/quizzes for this subject and term
    $activities = DB::table('activities')
        ->where('subject_id', $subject->id)
        ->where('term', $gradingClass->term)
        ->get();
    
    $linked = 0;
    $skipped = 0;
    
    foreach ($activities as $activity) {
        // Check if already linked
        $existing = DB::table('component_items')
            ->where('component_id', $component->id)
            ->where('activity_id', $activity->id)
            ->first();
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Create component item
        DB::table('component_items')->insert([
            'component_id' => $component->id,
            'activity_id' => $activity->id,
            'item_name' => $activity->name,
            'max_score' => $activity->max_score,
            'date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $linked++;
        echo "    ✓ Linked: {$activity->name} ({$activity->activity_category}, {$activity->type})\n";
    }
    
    echo "  Summary: {$linked} linked, {$skipped} already existed\n\n";
}

echo "Done!\n";
