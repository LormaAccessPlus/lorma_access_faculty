<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Find the Gender and Society subject
    $subject = DB::table('subjects')
        ->where('subject_code', 'GE101')
        ->where('section', 'CS101-1')
        ->first();
    
    if ($subject) {
        // Count and delete students from this subject
        $count = DB::table('student_subject')
            ->where('subject_id', $subject->id)
            ->count();
        
        DB::table('student_subject')
            ->where('subject_id', $subject->id)
            ->delete();
        
        echo "Successfully cleared {$count} student(s) from Gender and Society (GE101 - CS101-1).\n";
    } else {
        echo "Gender and Society subject not found.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
