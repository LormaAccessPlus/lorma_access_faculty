<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Get the original NURS101 subject
$originalNurs = DB::table('subjects')->where('id', 8)->first();

// 2. Update NURS101 to be Lab only
DB::table('subjects')->where('id', 8)->update([
    'subject_name' => 'Fundamentals of Nursing - Lab',
    'section' => 'NURS101-Lab',
    'type' => 'lab_only',
    'updated_at' => now()
]);

echo "✓ Updated NURS101 (ID: 8) to Lab only\n";

// 3. Create duplicate for Lecture
$newLectureId = DB::table('subjects')->insertGetId([
    'subject_code' => 'NURS101',
    'subject_name' => 'Fundamentals of Nursing - Lecture',
    'section' => 'NURS101-Lec',
    'type' => 'lecture_only',
    'matrix_type' => 'nursing',
    'faculty_id' => $originalNurs->faculty_id,
    'academic_year' => $originalNurs->academic_year,
    'semester' => $originalNurs->semester,
    'created_at' => now(),
    'updated_at' => now()
]);

echo "✓ Created NURS101 Lecture with ID: {$newLectureId}\n";

// 4. Update CS301 to be Lec+Lab
DB::table('subjects')->where('id', 9)->update([
    'subject_name' => 'Software Engineering - Lec+Lab',
    'section' => 'CS301-LecLab',
    'type' => 'lecture_lab',
    'updated_at' => now()
]);

echo "✓ Updated CS301 to Lec+Lab\n\n";

// Show all subjects
echo "Current subjects:\n";
$subjects = DB::table('subjects')->select('id', 'subject_code', 'subject_name', 'section', 'matrix_type')->get();
foreach ($subjects as $subject) {
    echo "  ID {$subject->id}: {$subject->subject_code} - {$subject->subject_name} ({$subject->section}) [{$subject->matrix_type}]\n";
}
