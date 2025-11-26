<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Get faculty by name
    $faculty = DB::table('faculties')
        ->where('name', 'LIKE', '%Leandro%Tenorio%')
        ->orWhere('name', 'LIKE', '%Tenorio%Leandro%')
        ->first();
    
    if (!$faculty) {
        echo "Faculty not found. Creating faculty record...\n";
        
        // Create faculty if doesn't exist
        $facultyId = DB::table('faculties')->insertGetId([
            'name' => 'Leandro Raphael Tenorio',
            'email' => 'leandro.tenorio@lorma.edu',
            'google_id' => null,
            'google_token' => null,
            'google_refresh_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "Faculty created with ID: {$facultyId}\n";
    } else {
        $facultyId = $faculty->id;
        echo "Faculty found: {$faculty->name} (ID: {$facultyId})\n";
    }
    
    // Check if subject already exists in app database
    $existingAppSubject = DB::table('subjects')
        ->where('subject_code', 'TEST101')
        ->where('faculty_id', $facultyId)
        ->first();
    
    if ($existingAppSubject) {
        echo "Subject TEST101 already exists (ID: {$existingAppSubject->id})\n";
        echo "✅ Subject is already available!\n";
    } else {
        // Add subject to app database
        $appSubjectId = DB::table('subjects')->insertGetId([
            'faculty_id' => $facultyId,
            'subject_code' => 'TEST101',
            'subject_name' => 'Test Subject for Grading System',
            'section' => 'Section A',
            'type' => 'lecture_only',
            'academic_year' => '2024-2025',
            'semester' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ Subject added successfully with ID: {$appSubjectId}\n";
    }
    
    echo "\n📚 Subject Details:\n";
    echo "   Code: TEST101\n";
    echo "   Name: Test Subject for Grading System\n";
    echo "   Section: Section A\n";
    echo "   Faculty: Leandro Raphael Tenorio\n";
    echo "\n🔗 Access it at: http://localhost:8000/subjects\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
