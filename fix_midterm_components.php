<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Remove the old "Activities" component (ID: 21) from CS301 Midterm
$component = DB::table('grading_components')->where('id', 21)->first();

if ($component) {
    echo "Found duplicate component to delete:\n";
    echo "  ID: {$component->id}\n";
    echo "  Name: {$component->component_name}\n";
    echo "  Type: {$component->component_type}\n";
    echo "  Weight: {$component->weight_percentage}%\n\n";
    
    // Delete component items first
    $items = DB::table('component_items')->where('component_id', 21)->get();
    echo "Deleting " . $items->count() . " component items...\n";
    foreach ($items as $item) {
        // Delete student grades for this item
        $gradesDeleted = DB::table('student_grades')->where('component_item_id', $item->id)->delete();
        echo "  - Deleted {$gradesDeleted} grades for item: {$item->item_name}\n";
    }
    DB::table('component_items')->where('component_id', 21)->delete();
    
    // Delete the component
    DB::table('grading_components')->where('id', 21)->delete();
    echo "\n✓ Deleted duplicate Activities component\n\n";
    
    // Show remaining components
    echo "Remaining components for Midterm:\n";
    $remaining = DB::table('grading_components')->where('grading_class_id', 8)->get();
    $totalWeight = 0;
    foreach ($remaining as $comp) {
        echo "  - {$comp->component_name} ({$comp->component_type}): {$comp->weight_percentage}%\n";
        $totalWeight += $comp->weight_percentage;
    }
    echo "\nTotal weight: {$totalWeight}%\n";
} else {
    echo "Component not found\n";
}
