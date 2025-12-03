<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportGcrStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:gcr-students {subject_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import students from Google Classroom and create database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subjectId = $this->argument('subject_id');
        $subject = \App\Models\Subject::find($subjectId);
        
        if (!$subject || !$subject->gcr_class_id) {
            $this->error("Subject not found or not connected to Google Classroom!");
            return 1;
        }
        
        $this->info("Importing students for: {$subject->subject_name}");
        
        // Authenticate
        $classroomService = app(\App\Services\GoogleClassroomService::class);
        if (!$classroomService->authenticateWithFaculty($subject->faculty)) {
            $this->error("Failed to authenticate!");
            return 1;
        }
        
        // Fetch students from GCR
        $gcrStudents = $classroomService->getStudents($subject->gcr_class_id);
        $this->info("Found " . count($gcrStudents) . " students in Google Classroom");
        $this->info("");
        
        $created = 0;
        $existing = 0;
        
        foreach ($gcrStudents as $gcrStudent) {
            $googleUserId = $gcrStudent['userId'];
            $fullName = $gcrStudent['profile']['name']['fullName'] ?? 'Unknown';
            $email = $gcrStudent['emailAddress'] ?? null;
            
            // Check if student already exists
            $student = \App\Models\Student::byGoogleUserId($googleUserId)->first();
            
            if (!$student && $email) {
                $student = \App\Models\Student::byEmail($email)->first();
            }
            
            if ($student) {
                // Update Google User ID if missing
                if (!$student->google_user_id) {
                    $student->google_user_id = $googleUserId;
                    $student->save();
                    $this->line("✓ Updated: {$fullName}");
                } else {
                    $this->line("- Exists: {$fullName}");
                }
                $existing++;
            } else {
                // Create new student
                $nameParts = $this->parseStudentName($fullName);
                
                $student = \App\Models\Student::create([
                    'student_number' => 'GCR' . substr($googleUserId, -8),
                    'first_name' => $nameParts['first_name'],
                    'middle_name' => $nameParts['middle_name'],
                    'last_name' => $nameParts['last_name'],
                    'email' => $email ?? "gcr.{$googleUserId}@temp.edu",
                    'google_user_id' => $googleUserId,
                    'status' => 'active',
                ]);
                
                $this->info("+ Created: {$fullName}");
                $created++;
            }
            
            // Enroll in subject if not already enrolled
            if (!$student->subjects()->where('subject_id', $subject->id)->exists()) {
                $student->subjects()->attach($subject->id, ['status' => 'enrolled']);
                $this->line("  → Enrolled in subject");
            }
        }
        
        $this->info("");
        $this->info("Summary:");
        $this->info("  Created: {$created}");
        $this->info("  Existing: {$existing}");
        $this->info("  Total: " . ($created + $existing));
        
        return 0;
    }
    
    private function parseStudentName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName));
        $count = count($parts);

        if ($count === 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[0],
            ];
        } elseif ($count === 2) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[1],
            ];
        } else {
            return [
                'first_name' => $parts[0],
                'middle_name' => implode(' ', array_slice($parts, 1, -1)),
                'last_name' => $parts[$count - 1],
            ];
        }
    }
}
