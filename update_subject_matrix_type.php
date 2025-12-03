<?php
/**
 * Quick script to update a subject's matrix_type to 'nursing'
 * 
 * Usage: php update_subject_matrix_type.php
 * 
 * This will update the subject with ID 8 to use the nursing matrix type.
 * Change the $subjectId variable to match your subject ID.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Change this to your subject ID
$subjectId = 8;

$subject = \App\Models\Subject::find($subjectId);

if (!$subject) {
    echo "Subject with ID {$subjectId} not found!\n";
    exit(1);
}

echo "Current subject: {$subject->subject_code} - {$subject->subject_name}\n";
echo "Current matrix_type: " . ($subject->matrix_type ?? 'NULL') . "\n";

$subject->matrix_type = 'nursing';
$subject->save();

echo "✓ Updated matrix_type to 'nursing'\n";
echo "\nNow refresh your page and the exam grades should calculate correctly!\n";
