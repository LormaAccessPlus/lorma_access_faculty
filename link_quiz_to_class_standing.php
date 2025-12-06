<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the Class Standing component for Prelim
$component = DB::table('grading_components')
    ->where('grading_class_id', 7)
    ->where('component_type', 'class_standing')
    ->first();

if (!$component) {
    echo "Class Standing component not found!\n";
    exit(1);
}

echo "Component: {$component->component_name} (ID: {$component->id})\n\n";

// Get ALL activities and quizzes for this subject/term
$activities = DB::table('activities')
    ->where('subject_id', 9)
    ->where('term', 'prelim')
    ->get();

echo "Found " . $activities->count() . " activities/quizzes\n\n";

foreach ($activities as $activity) {
    // Check if already linked
    $existing = DB::table('component_items')
        ->where('component_id', $component->id)
        ->where('activity_id', $activity->id)
        ->first();
    
    if ($existing) {
        echo "  - {$activity->name} ({$activity->activity_category}, {$activity->type}) - Already linked\n";
        continue;
    }
    
    // Create component item
    $itemId = DB::table('component_items')->insertGetId([
        'component_id' => $component->id,
        'activity_id' => $activity->id,
        'item_name' => $activity->name,
        'max_score' => $activity->max_score,
        'date' => now()->format('Y-m-d'),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "  ✓ Linked {$activity->name} ({$activity->activity_category}, {$activity->type}) as component item (ID: {$itemId})\n";
}

echo "\nDone! All activities and quizzes are now linked to Class Standing.\n";
