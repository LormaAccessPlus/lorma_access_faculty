<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the Exam component for NURS101 Prelim
$examComponent = DB::table('grading_components')
    ->where('grading_class_id', 10)
    ->where('component_type', 'exam')
    ->first();

if (!$examComponent) {
    echo "Exam component not found!\n";
    exit(1);
}

echo "Exam Component: {$examComponent->component_name} (ID: {$examComponent->id})\n";
echo "Weight: {$examComponent->weight_percentage}%\n";
echo "Formula: {$examComponent->formula}\n\n";

// Check if exam item already exists
$existingItem = DB::table('component_items')
    ->where('component_id', $examComponent->id)
    ->first();

if ($existingItem) {
    echo "Exam item already exists: {$existingItem->item_name}\n";
    exit(0);
}

// Add exam item
$itemId = DB::table('component_items')->insertGetId([
    'component_id' => $examComponent->id,
    'activity_id' => null,
    'item_name' => 'Prelim Exam',
    'max_score' => 100.00,
    'date' => now()->format('Y-m-d'),
    'created_at' => now(),
    'updated_at' => now()
]);

echo "✓ Added exam item: Prelim Exam (ID: {$itemId})\n";
echo "  Max Score: 100\n";
echo "  Formula will be applied: {$examComponent->formula}\n";
