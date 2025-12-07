<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Usage: php debug_export.php [archive|unarchive] [subject_id]
$action = $argv[1] ?? 'list';
$subjectId = $argv[2] ?? null;

if ($action === 'archive' && $subjectId) {
    $subject = \App\Models\Subject::find($subjectId);
    if ($subject) {
        $subject->update(['gcr_course_state' => 'ARCHIVED']);
        echo "Subject {$subject->subject_code} has been archived.\n";
    } else {
        echo "Subject not found.\n";
    }
} elseif ($action === 'unarchive' && $subjectId) {
    $subject = \App\Models\Subject::find($subjectId);
    if ($subject) {
        $subject->update(['gcr_course_state' => 'ACTIVE']);
        echo "Subject {$subject->subject_code} has been unarchived.\n";
    } else {
        echo "Subject not found.\n";
    }
} else {
    echo "Subjects:\n";
    $subjects = \App\Models\Subject::all(['id', 'subject_code', 'subject_name', 'gcr_course_state']);
    foreach ($subjects as $s) {
        $status = $s->gcr_course_state === 'ARCHIVED' ? '[ARCHIVED]' : '[ACTIVE]';
        echo "  {$s->id}: {$s->subject_code} - {$s->subject_name} {$status}\n";
    }
    echo "\nUsage:\n";
    echo "  php debug_export.php archive <subject_id>   - Archive a subject\n";
    echo "  php debug_export.php unarchive <subject_id> - Unarchive a subject\n";
}
