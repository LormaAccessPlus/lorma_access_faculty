<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Models\Faculty;
use App\Services\GoogleClassroomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncArchivedClassrooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classrooms:sync-archived';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync archived status from Google Classroom to subjects';

    protected $classroomService;

    public function __construct(GoogleClassroomService $classroomService)
    {
        parent::__construct();
        $this->classroomService = $classroomService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing archived classrooms from Google Classroom...');
        
        // Get all subjects that are connected to GCR and not yet archived
        $subjects = Subject::whereNotNull('gcr_class_id')
            ->active()
            ->with('faculty')
            ->get();
        
        $archivedCount = 0;
        
        foreach ($subjects as $subject) {
            try {
                $this->info("Checking subject: {$subject->subject_code} (GCR ID: {$subject->gcr_class_id})");
                
                // Authenticate with faculty's Google account
                if (!$this->classroomService->authenticateWithFaculty($subject->faculty)) {
                    $this->warn("Could not authenticate for faculty: {$subject->faculty->name}");
                    continue;
                }
                
                // Get course details from GCR
                $course = $this->classroomService->getCourse($subject->gcr_class_id);
                
                if ($course) {
                    $courseState = $course['course_state'] ?? 'UNKNOWN';
                    $this->info("  Course state: {$courseState}");
                    
                    if ($courseState === 'ARCHIVED') {
                        // Archive the subject
                        $subject->update([
                            'archived_at' => now(),
                            'gcr_course_state' => 'ARCHIVED'
                        ]);
                        
                        $archivedCount++;
                        $this->info("  ✓ Archived: {$subject->subject_code} - {$subject->subject_name}");
                        
                        Log::info('Subject archived automatically', [
                            'subject_id' => $subject->id,
                            'subject_code' => $subject->subject_code,
                            'gcr_class_id' => $subject->gcr_class_id
                        ]);
                    }
                } else {
                    $this->warn("  Could not fetch course details");
                }
                
            } catch (\Exception $e) {
                $this->error("Error processing subject {$subject->id}: " . $e->getMessage());
                Log::error('Error syncing archived classroom', [
                    'subject_id' => $subject->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("Sync complete. Archived {$archivedCount} subjects.");
        
        return 0;
    }
}
