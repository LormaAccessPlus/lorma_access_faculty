<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestGoogleClassroomStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:gcr-students {subject_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test fetching students from Google Classroom';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subjectId = $this->argument('subject_id');
        $subject = \App\Models\Subject::find($subjectId);
        
        if (!$subject) {
            $this->error("Subject not found!");
            return 1;
        }
        
        if (!$subject->gcr_class_id) {
            $this->error("Subject has no Google Classroom connected!");
            return 1;
        }
        
        $this->info("Subject: {$subject->subject_name}");
        $this->info("GCR Class ID: {$subject->gcr_class_id}");
        $this->info("");
        
        // Get faculty
        $faculty = $subject->faculty;
        if (!$faculty) {
            $this->error("No faculty found for this subject!");
            return 1;
        }
        
        // Authenticate
        $classroomService = app(\App\Services\GoogleClassroomService::class);
        if (!$classroomService->authenticateWithFaculty($faculty)) {
            $this->error("Failed to authenticate with Google Classroom!");
            $this->warn("You may need to re-authenticate. Visit the Google Classroom page to reconnect.");
            return 1;
        }
        
        $this->info("✓ Authenticated successfully");
        $this->info("");
        
        // Fetch students
        try {
            $students = $classroomService->getStudents($subject->gcr_class_id);
            
            $this->info("Found " . count($students) . " students:");
            $this->info("");
            
            foreach ($students as $student) {
                $this->line("Student ID: " . ($student['userId'] ?? 'N/A'));
                $this->line("Name: " . ($student['profile']['name']['fullName'] ?? 'Unknown'));
                $this->line("Email: " . ($student['emailAddress'] ?? 'NO EMAIL'));
                $this->line("---");
            }
            
            // Show raw data for first student
            if (count($students) > 0) {
                $this->info("");
                $this->info("Raw data for first student:");
                $this->line(json_encode($students[0], JSON_PRETTY_PRINT));
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to fetch students: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
