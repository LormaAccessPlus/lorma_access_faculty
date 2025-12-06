<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Remove the old "Activities" component (ID: 19) from CS301 Prelim
$component = DB::table('grading_components')->where('id', 19)->first();

if ($component) {
    echo "Found component to delete:\n";
    echo "  ID: {$component->id}\n";
    echo "  Name: {$component->component_name}\n";
    echo "  Type: {$component->component_type}\n";
    echo "  Weight: {$component->weight_percentage}%\n\n";
    
    // Delete component items first
    $items = DB::table('component_items')->where('component_id', 19)->get();
    echo "Deleting " . $items->count() . " component items...\n";
    foreach ($items as $item) {
        // Delete student grades for this item
        DB::table('student_grades')->where('component_item_id', $item->id)->delete();
        echo "  - Deleted grades for item: {$item->item_name}\n";
    }
    DB::table('component_items')->where('component_id', 19)->delete();
    
    // Delete the component
    DB::table('grading_components')->where('id', 19)->delete();
    echo "\n✓ Deleted component\n";
} else {
    echo "Component not found\n";
}
