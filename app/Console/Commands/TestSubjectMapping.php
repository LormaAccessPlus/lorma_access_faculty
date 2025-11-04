<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubjectMappingService;
use App\Models\Faculty;

class TestSubjectMapping extends Command
{
    protected $signature = 'test:subject-mapping {faculty_id=1}';
    protected $description = 'Test the subject mapping service';

    public function handle(SubjectMappingService $mappingService)
    {
        $facultyId = $this->argument('faculty_id');
        
        $this->info("Testing Subject Mapping Service for Faculty ID: {$facultyId}");
        
        // Test Google Classroom courses
        $this->info("Fetching Google Classroom courses...");
        try {
            $gcrCourses = $mappingService->getAvailableGoogleClassroomCourses($facultyId);
            $this->info("Found " . count($gcrCourses) . " Google Classroom courses:");
            
            foreach ($gcrCourses as $course) {
                $this->line("  - {$course['name']} (ID: {$course['id']})");
            }
        } catch (\Exception $e) {
            $this->error("Failed to fetch Google Classroom courses: " . $e->getMessage());
        }
        
        // Test school database subjects
        $this->info("\nFetching school database subjects...");
        try {
            $faculty = Faculty::find($facultyId);
            if (!$faculty) {
                $this->error("Faculty not found with ID: {$facultyId}");
                return 1;
            }
            
            $schoolSubjects = $mappingService->getAvailableSchoolSubjects(
                $faculty,
                '2024-2025',
                '1'
            );
            $this->info("Found " . count($schoolSubjects) . " school database subjects:");
            
            foreach ($schoolSubjects as $subject) {
                $this->line("  - {$subject['subject_name']} ({$subject['subject_code']})");
            }
        } catch (\Exception $e) {
            $this->error("Failed to fetch school database subjects: " . $e->getMessage());
        }
        
        // Test mapping statistics
        $this->info("\nFetching mapping statistics...");
        try {
            $stats = $mappingService->getMappingStatistics($facultyId);
            $this->info("Mapping Statistics:");
            $this->line("  - Total Subjects: {$stats['total_subjects']}");
            $this->line("  - Pending Mapping: {$stats['pending_mapping']}");
            $this->line("  - Completed Mapping: {$stats['completed_mapping']}");
            $this->line("  - Total Student Mappings: {$stats['total_student_mappings']}");
        } catch (\Exception $e) {
            $this->error("Failed to fetch mapping statistics: " . $e->getMessage());
        }
        
        return 0;
    }
}