<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$subjectId = 8; // NURS101 Lab

echo "=== Complete Reset for NURS101 Lab (Subject ID: {$subjectId}) ===\n\n";

// 1. Delete grading classes and related data
echo "1. Deleting grading classes...\n";
$gradingClasses = DB::table('grading_classes')->where('subject_id', $subjectId)->get();
foreach ($gradingClasses as $class) {
    // Delete component items
    $components = DB::table('grading_components')->where('grading_class_id', $class->id)->pluck('id');
    if ($components->count() > 0) {
        DB::table('component_items')->whereIn('component_id', $components)->delete();
        echo "   - Deleted component items for class {$class->id}\n";
    }
    
    // Delete components
    DB::table('grading_components')->where('grading_class_id', $class->id)->delete();
    echo "   - Deleted components for class {$class->id}\n";
    
    // Delete student grades
    DB::table('student_grades')->where('grading_class_id', $class->id)->delete();
    echo "   - Deleted student grades for class {$class->id}\n";
}
DB::table('grading_classes')->where('subject_id', $subjectId)->delete();
echo "   ✓ Deleted grading classes\n\n";

// 2. Delete grading configs
echo "2. Deleting grading configs...\n";
DB::table('grading_configs')->where('subject_id', $subjectId)->delete();
echo "   ✓ Deleted\n\n";

// 3. Delete term grades
echo "3. Deleting term grades...\n";
DB::table('term_grades')->where('subject_id', $subjectId)->delete();
echo "   ✓ Deleted\n\n";

// 4. Delete grade records
echo "4. Deleting grade records...\n";
$activityIds = DB::table('activities')->where('subject_id', $subjectId)->pluck('id');
if ($activityIds->count() > 0) {
    DB::table('grade_records')->whereIn('activity_id', $activityIds)->delete();
    echo "   ✓ Deleted grade records\n\n";
}

// 5. Delete activities
echo "5. Deleting activities...\n";
DB::table('activities')->where('subject_id', $subjectId)->delete();
echo "   ✓ Deleted activities\n\n";

// 6. Verify subject settings
$subject = DB::table('subjects')->where('id', $subjectId)->first();
echo "6. Subject configuration:\n";
echo "   - Name: {$subject->subject_name}\n";
echo "   - Type: {$subject->type}\n";
echo "   - Matrix Type: {$subject->matrix_type}\n\n";

echo "=== Reset Complete ===\n";
echo "The subject is now clean and ready for fresh setup as a Lab-only course.\n";
