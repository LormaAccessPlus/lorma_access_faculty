<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Subject;
use App\Models\Activity;
use App\Services\GoogleClassroomService;
use Illuminate\Support\Facades\Auth;

echo "=== Google Classroom Grade Import Debugger ===\n\n";

// Get the subject
$subjectId = readline("Enter Subject ID: ");
$subject = Subject::find($subjectId);

if (!$subject) {
    echo "❌ Subject not found!\n";
    exit(1);
}

echo "Subject: {$subject->subject_code} - {$subject->subject_name}\n";
echo "GCR Class ID: {$subject->gcr_class_id}\n\n";

if (!$subject->gcr_class_id) {
    echo "❌ Subject not connected to Google Classroom!\n";
    exit(1);
}

// Get the faculty
$faculty = \App\Models\Faculty::first();
if (!$faculty) {
    echo "❌ No faculty found!\n";
    exit(1);
}

echo "Faculty: {$faculty->name}\n\n";

// Initialize Google Classroom Service
$classroomService = new GoogleClassroomService();

// Authenticate
echo "Authenticating with Google Classroom...\n";
if (!$classroomService->authenticateWithFaculty($faculty)) {
    echo "❌ Failed to authenticate!\n";
    exit(1);
}
echo "✓ Authenticated\n\n";

// Get activities
$term = readline("Enter term (prelim/midterm/finals): ");
$activities = Activity::where('subject_id', $subject->id)
    ->where('term', $term)
    ->get();

if ($activities->isEmpty()) {
    echo "❌ No activities found for term: {$term}\n";
    exit(1);
}

echo "Found {$activities->count()} activities for {$term}:\n";
foreach ($activities as $activity) {
    echo "  - {$activity->name} (ID: {$activity->id}, GCR ID: {$activity->gcr_assignment_id})\n";
}
echo "\n";

// Select an activity
$activityId = readline("Enter Activity ID to check: ");
$activity = $activities->firstWhere('id', $activityId);

if (!$activity) {
    echo "❌ Activity not found!\n";
    exit(1);
}

if (!$activity->gcr_assignment_id) {
    echo "❌ Activity not connected to Google Classroom!\n";
    exit(1);
}

echo "\n=== Fetching Submissions for: {$activity->name} ===\n";
echo "GCR Assignment ID: {$activity->gcr_assignment_id}\n";
echo "Max Score: {$activity->max_score}\n\n";

try {
    $submissions = $classroomService->getStudentSubmissions(
        $subject->gcr_class_id,
        $activity->gcr_assignment_id
    );
    
    echo "Found " . count($submissions) . " submissions:\n\n";
    
    foreach ($submissions as $submission) {
        echo "Student ID: {$submission['user_id']}\n";
        echo "  State: {$submission['state']}\n";
        echo "  Draft Grade: " . ($submission['draft_grade'] ?? 'NULL') . "\n";
        echo "  Assigned Grade: " . ($submission['assigned_grade'] ?? 'NULL') . "\n";
        echo "  Late: " . ($submission['late'] ? 'Yes' : 'No') . "\n";
        echo "  Created: {$submission['creation_time']}\n";
        echo "  Updated: {$submission['update_time']}\n";
        
        // Check what would be imported
        $score = $submission['assigned_grade'] ?? $submission['draft_grade'] ?? null;
        if ($score !== null) {
            echo "  ✓ WOULD IMPORT: {$score}\n";
        } else {
            echo "  ❌ NO GRADE TO IMPORT\n";
        }
        echo "\n";
    }
    
    echo "\n=== Summary ===\n";
    $withDraft = count(array_filter($submissions, fn($s) => isset($s['draft_grade'])));
    $withAssigned = count(array_filter($submissions, fn($s) => isset($s['assigned_grade'])));
    $withAny = count(array_filter($submissions, fn($s) => isset($s['draft_grade']) || isset($s['assigned_grade'])));
    
    echo "Submissions with draft_grade: {$withDraft}\n";
    echo "Submissions with assigned_grade: {$withAssigned}\n";
    echo "Submissions that would be imported: {$withAny}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Done! ===\n";
