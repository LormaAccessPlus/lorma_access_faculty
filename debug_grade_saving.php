<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Subject;
use App\Models\Faculty;
use App\Models\Activity;
use App\Models\StudentMapping;

try {
    echo "Debugging Grade Saving Issues...\n";
    
    $faculty = Faculty::where('email', 'leandroraphael.tenorio@lorma.edu')->first();
    $subject = Subject::where('faculty_id', $faculty->id)->first();
    
    echo "Subject: {$subject->subject_code} - {$subject->subject_name}\n";
    
    // Check if the grade update route exists
    echo "\n=== CHECKING ROUTES ===\n";
    try {
        $updateRoute = route('grades.update');
        echo "✅ Grade update route exists: {$updateRoute}\n";
    } catch (Exception $e) {
        echo "❌ Grade update route missing: " . $e->getMessage() . "\n";
    }
    
    try {
        $examScoreRoute = route('grades.update-exam-score');
        echo "✅ Exam score update route exists: {$examScoreRoute}\n";
    } catch (Exception $e) {
        echo "❌ Exam score update route missing: " . $e->getMessage() . "\n";
    }
    
    // Check activities for this subject
    echo "\n=== CHECKING ACTIVITIES ===\n";
    $activities = Activity::where('subject_id', $subject->id)->get();
    echo "Found " . count($activities) . " activities:\n";
    
    foreach ($activities as $activity) {
        echo "- {$activity->name} (ID: {$activity->id}, Type: {$activity->type}, Term: {$activity->term})\n";
    }
    
    // Check student mappings
    echo "\n=== CHECKING STUDENT MAPPINGS ===\n";
    $studentMappings = StudentMapping::where('subject_id', $subject->id)->get();
    echo "Found " . count($studentMappings) . " student mappings:\n";
    
    foreach ($studentMappings as $mapping) {
        echo "- {$mapping->student_name} (ID: {$mapping->id})\n";
    }
    
    // Check if GradeController exists
    echo "\n=== CHECKING GRADE CONTROLLER ===\n";
    if (class_exists('App\Http\Controllers\GradeController')) {
        echo "✅ GradeController exists\n";
        
        // Check if the updateGrade method exists
        $controller = new \App\Http\Controllers\GradeController();
        if (method_exists($controller, 'updateGrade')) {
            echo "✅ updateGrade method exists\n";
        } else {
            echo "❌ updateGrade method missing\n";
        }
        
        if (method_exists($controller, 'updateExamScore')) {
            echo "✅ updateExamScore method exists\n";
        } else {
            echo "❌ updateExamScore method missing\n";
        }
    } else {
        echo "❌ GradeController missing\n";
    }
    
    // Check Grade model
    echo "\n=== CHECKING GRADE MODEL ===\n";
    if (class_exists('App\Models\Grade')) {
        echo "✅ Grade model exists\n";
    } else {
        echo "❌ Grade model missing - this might be the issue!\n";
    }
    
    // Check GradeRecord model
    if (class_exists('App\Models\GradeRecord')) {
        echo "✅ GradeRecord model exists\n";
    } else {
        echo "❌ GradeRecord model missing\n";
    }
    
    echo "\n=== RECOMMENDATIONS ===\n";
    echo "1. Check if the Grade/GradeRecord model exists\n";
    echo "2. Verify the GradeController has the updateGrade method\n";
    echo "3. Check database connection and table structure\n";
    echo "4. Look at browser console for JavaScript errors\n";
    echo "5. Check Laravel logs for server-side errors\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}