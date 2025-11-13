<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Services\GradeStorageService;
use Illuminate\Console\Command;

class SyncGradesToSchool extends Command
{
    protected $signature = 'grades:sync {subject_id} {academic_year} {semester}';
    protected $description = 'Sync grades from faculty app to school database';

    public function handle(GradeStorageService $gradeStorageService)
    {
        $subjectId = $this->argument('subject_id');
        $academicYear = $this->argument('academic_year');
        $semester = $this->argument('semester');

        $subject = Subject::find($subjectId);

        if (!$subject) {
            $this->error("Subject not found with ID: {$subjectId}");
            return 1;
        }

        $this->info("Syncing grades for: {$subject->subject_code} - {$subject->subject_name}");
        $this->info("Academic Year: {$academicYear}, Semester: {$semester}");

        $results = $gradeStorageService->syncGradesToSchoolDatabase($subject, $academicYear, $semester);

        if ($results['success']) {
            $this->info("✓ Successfully synced {$results['stored_count']} grades!");
        } else {
            $this->error("✗ Sync failed!");
            $this->error("Stored: {$results['stored_count']}, Failed: {$results['failed_count']}");
            
            if (!empty($results['errors'])) {
                $this->error("\nErrors:");
                foreach ($results['errors'] as $error) {
                    $this->error("  - " . (is_array($error) ? json_encode($error) : $error));
                }
            }
        }

        return $results['success'] ? 0 : 1;
    }
}
