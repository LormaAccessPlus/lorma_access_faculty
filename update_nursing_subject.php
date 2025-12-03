<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    // Update the SOFTENG subject to a nursing subject
    $subjectId = 8;
    
    DB::table('subjects')
        ->where('id', $subjectId)
        ->update([
            'subject_code' => 'NURS101',
            'subject_name' => 'Fundamentals of Nursing',
            'section' => 'NURS101-1',
            'updated_at' => now()
        ]);

    echo "✓ Updated subject ID {$subjectId} to Nursing subject\n";
    echo "  - Subject Code: NURS101\n";
    echo "  - Subject Name: Fundamentals of Nursing\n";
    echo "  - Section: NURS101-1\n\n";

    // Add the three students
    $students = [
        [
            'student_number' => '2021-00001',
            'first_name' => 'Mark Joshua',
            'middle_name' => null,
            'last_name' => 'Navida',
            'email' => 'markjoshua.navida@example.com',
            'course' => 'BSN',
            'year_level' => 2,
            'section' => 'BSN-2A',
            'status' => 'active'
        ],
        [
            'student_number' => '2021-00002',
            'first_name' => 'Leandro Raphael',
            'middle_name' => null,
            'last_name' => 'Tenorio',
            'email' => 'leandroraphael.tenorio@example.com',
            'course' => 'BSN',
            'year_level' => 2,
            'section' => 'BSN-2A',
            'status' => 'active'
        ],
        [
            'student_number' => '2021-00003',
            'first_name' => 'Jasper Ace',
            'middle_name' => null,
            'last_name' => 'Lapitan',
            'email' => 'jasperace.lapitan@example.com',
            'course' => 'BSN',
            'year_level' => 2,
            'section' => 'BSN-2A',
            'status' => 'active'
        ]
    ];

    echo "Adding students:\n";
    
    foreach ($students as $studentData) {
        // Check if student already exists
        $existing = DB::table('students')
            ->where('student_number', $studentData['student_number'])
            ->orWhere('email', $studentData['email'])
            ->first();

        if ($existing) {
            echo "  ⚠ Student {$studentData['first_name']} {$studentData['last_name']} already exists (ID: {$existing->id})\n";
            $studentId = $existing->id;
        } else {
            $studentId = DB::table('students')->insertGetId(array_merge($studentData, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            echo "  ✓ Added {$studentData['first_name']} {$studentData['last_name']} (ID: {$studentId})\n";
        }

        // Link student to the nursing subject
        $existingLink = DB::table('student_subject')
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->first();

        if (!$existingLink) {
            DB::table('student_subject')->insert([
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "    → Enrolled in NURS101\n";
        } else {
            echo "    → Already enrolled in subject\n";
        }
    }

    DB::commit();

    echo "\n✅ Successfully updated subject and added students!\n\n";

    // Display summary
    echo "=== SUMMARY ===\n";
    $subject = DB::table('subjects')->where('id', $subjectId)->first();
    echo "Subject: {$subject->subject_name} ({$subject->subject_code})\n";
    echo "Section: {$subject->section}\n";
    echo "Type: {$subject->type}\n";
    echo "Academic Year: {$subject->academic_year}\n";
    echo "Semester: {$subject->semester}\n\n";

    $enrolledStudents = DB::table('students')
        ->join('student_subject', 'students.id', '=', 'student_subject.student_id')
        ->where('student_subject.subject_id', $subjectId)
        ->select('students.*')
        ->get();

    echo "Enrolled Students (" . count($enrolledStudents) . "):\n";
    foreach ($enrolledStudents as $student) {
        echo "  - {$student->first_name} {$student->last_name} ({$student->student_number})\n";
    }

} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
