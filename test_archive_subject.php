<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Subject;

echo "=== Archive Subject Test ===\n\n";

// List all subjects
$subjects = Subject::all();
echo "Available Subjects:\n";
foreach ($subjects as $subject) {
    $state = $subject->gcr_course_state ?? 'NULL';
    echo "  {$subject->id}: {$subject->subject_code} - {$subject->subject_name} (State: {$state})\n";
}

echo "\n";
$subjectId = readline("Enter Subject ID to archive (or 'q' to quit): ");

if ($subjectId === 'q') {
    echo "Cancelled.\n";
    exit(0);
}

$subject = Subject::find($subjectId);

if (!$subject) {
    echo "❌ Subject not found!\n";
    exit(1);
}

echo "\nSubject: {$subject->subject_code} - {$subject->subject_name}\n";
echo "Current State: " . ($subject->gcr_course_state ?? 'NULL') . "\n\n";

$action = readline("Archive this subject? (y/n): ");

if (strtolower($action) === 'y') {
    $subject->update(['gcr_course_state' => 'ARCHIVED']);
    echo "✓ Subject archived!\n";
    echo "\nNow try accessing:\n";
    echo "  - Grade Matrix: /subjects/{$subject->id}/grades/matrix\n";
    echo "  - Term Grading: /subjects/{$subject->id}/grades/term/prelim\n";
} else {
    echo "Cancelled.\n";
}

echo "\n=== Done! ===\n";
