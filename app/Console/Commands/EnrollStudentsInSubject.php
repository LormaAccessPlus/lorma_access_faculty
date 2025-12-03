<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnrollStudentsInSubject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enroll:students {subject_id} {--student_ids=* : Student IDs to enroll}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enroll students in a subject';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subjectId = $this->argument('subject_id');
        $studentIds = $this->option('student_ids');
        
        $subject = \App\Models\Subject::find($subjectId);
        if (!$subject) {
            $this->error("Subject not found!");
            return 1;
        }
        
        $this->info("Subject: {$subject->subject_name}");
        $this->info("");
        
        // If no student IDs provided, show all students and ask
        if (empty($studentIds)) {
            $this->info("Available students:");
            $students = \App\Models\Student::all();
            foreach ($students as $student) {
                $this->line("  {$student->id}. {$student->full_name} ({$student->student_number})");
            }
            $this->info("");
            $this->info("Usage: php artisan enroll:students {$subjectId} --student_ids=1 --student_ids=2 --student_ids=3");
            return 0;
        }
        
        $enrolled = 0;
        $alreadyEnrolled = 0;
        
        foreach ($studentIds as $studentId) {
            $student = \App\Models\Student::find($studentId);
            if (!$student) {
                $this->warn("Student ID {$studentId} not found, skipping...");
                continue;
            }
            
            // Check if already enrolled
            if ($student->subjects()->where('subject_id', $subjectId)->exists()) {
                $this->line("- {$student->full_name} already enrolled");
                $alreadyEnrolled++;
            } else {
                $student->subjects()->attach($subjectId, ['status' => 'enrolled']);
                $this->info("✓ Enrolled {$student->full_name}");
                $enrolled++;
            }
        }
        
        $this->info("");
        $this->info("Summary:");
        $this->info("  Newly enrolled: {$enrolled}");
        $this->info("  Already enrolled: {$alreadyEnrolled}");
        
        return 0;
    }
}
