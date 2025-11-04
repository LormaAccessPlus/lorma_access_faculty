<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING ACTIVITIES FOR COMPRO1 ===\n";

// Find COMPRO1 subject
$subject = \App\Models\Subject::where('subject_code', 'COMPRO1')->first();

if (!$subject) {
    echo "❌ COMPRO1 subject not found\n";
    exit;
}

echo "✅ Subject found: {$subject->subject_code} - {$subject->subject_name}\n";
echo "Subject ID: {$subject->id}\n\n";

// Get all activities for this subject
$allActivities = \App\Models\Activity::where('subject_id', $subject->id)->get();
echo "=== ALL ACTIVITIES ===\n";
echo "Total activities: {$allActivities->count()}\n";

foreach ($allActivities as $activity) {
    echo "- ID: {$activity->id}, Name: {$activity->name}, Term: {$activity->term}, Type: {$activity->type}, Max Score: {$activity->max_score}\n";
}

// Check activities by term
echo "\n=== ACTIVITIES BY TERM ===\n";
foreach (['prelim', 'midterm', 'finals'] as $term) {
    $termActivities = \App\Models\Activity::where('subject_id', $subject->id)
        ->where('term', $term)
        ->get();
    
    echo "{$term}: {$termActivities->count()} activities\n";
    foreach ($termActivities as $activity) {
        echo "  - {$activity->name} ({$activity->type})\n";
    }
}

// Check what the GradeController would load
echo "\n=== WHAT GRADECONTROLLER LOADS ===\n";
$subject->load([
    'activities' => function ($query) {
        $query->where('term', 'prelim')->orderBy('type')->orderBy('created_at');
    }
]);

echo "Activities loaded for prelim term: {$subject->activities->count()}\n";
foreach ($subject->activities as $activity) {
    echo "- {$activity->name} ({$activity->type})\n";
}

// Check lecture and lab activities separately
$lectureActivities = $subject->activities->where('type', 'lecture');
$labActivities = $subject->activities->where('type', 'lab');

echo "\nLecture activities: {$lectureActivities->count()}\n";
echo "Lab activities: {$labActivities->count()}\n";