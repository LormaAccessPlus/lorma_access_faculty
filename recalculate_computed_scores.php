<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function applyFormula($score, $total, $formula) {
    try {
        $expression = str_replace(['score', 'total'], [$score, $total], $formula);
        $result = eval("return {$expression};");
        return round($result, 2);
    } catch (\Exception $e) {
        return null;
    }
}

// Get all student grades
$grades = DB::table('student_grades')->get();

echo "Recalculating computed scores for " . $grades->count() . " grades...\n\n";

$updated = 0;
foreach ($grades as $grade) {
    // Get the component item
    $item = DB::table('component_items')->where('id', $grade->component_item_id)->first();
    if (!$item) continue;
    
    // Get the component
    $component = DB::table('grading_components')->where('id', $item->component_id)->first();
    if (!$component || !$component->formula) continue;
    
    // Recalculate computed score
    if ($grade->score !== null) {
        $newComputed = applyFormula($grade->score, $item->max_score, $component->formula);
        
        if ($newComputed != $grade->computed_score) {
            DB::table('student_grades')
                ->where('id', $grade->id)
                ->update(['computed_score' => $newComputed]);
            
            echo "Updated grade ID {$grade->id}: {$grade->score}/{$item->max_score} → {$newComputed} (was {$grade->computed_score})\n";
            $updated++;
        }
    }
}

echo "\n✓ Updated {$updated} grades\n";
