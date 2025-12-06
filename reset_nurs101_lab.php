<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get NURS101 Lab subject (ID: 8)
$subject = DB::table('subjects')->where('id', 8)->first();

echo "Subject: {$subject->subject_name} (ID: {$subject->id})\n";
echo "Type: {$subject->type}\n";
echo "Matrix Type: {$subject->matrix_type}\n\n";

// Check grading configs
$configs = DB::table('grading_configs')
    ->where('subject_id', 8)
    ->where('matrix_type', 'nursing')
    ->get();

echo "Found " . $configs->count() . " nursing grading configs\n\n";

foreach ($configs as $config) {
    echo "Term: {$config->term}\n";
    echo "Custom Formulas: " . ($config->custom_formulas ?? 'null') . "\n";
    echo "Formula Config: {$config->formula_config}\n";
    echo "---\n";
}

// Delete all existing grading configs for this subject
echo "\nDeleting all grading configs for subject ID 8...\n";
DB::table('grading_configs')->where('subject_id', 8)->delete();
echo "✓ Deleted\n\n";

// Delete all term grades for this subject
echo "Deleting all term grades for subject ID 8...\n";
DB::table('term_grades')->where('subject_id', 8)->delete();
echo "✓ Deleted\n\n";

echo "Subject has been reset. You can now set up the grading configuration for the Lab-only course.\n";
