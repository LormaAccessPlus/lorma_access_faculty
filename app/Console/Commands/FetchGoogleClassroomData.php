<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleClassroomService;
use App\Models\Faculty;

class FetchGoogleClassroomData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classroom:fetch {email : Faculty email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Google Classroom data for a faculty member';

    private GoogleClassroomService $classroomService;

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
        $email = $this->argument('email');
        
        // Find faculty by email
        $faculty = Faculty::where('email', $email)->first();
        
        if (!$faculty) {
            $this->error("Faculty member with email '{$email}' not found.");
            $this->info("Available faculty members:");
            Faculty::all()->each(function ($f) {
                $this->line("- {$f->email} ({$f->name})");
            });
            return 1;
        }

        $this->info("Fetching Google Classroom data for: {$faculty->name} ({$faculty->email})");
        
        // Show token info
        $this->info("Token expires at: " . ($faculty->token_expires_at ?? 'Unknown'));
        $this->info("Current time: " . now());
        $this->info("Token expired: " . (now()->gt($faculty->token_expires_at) ? 'Yes' : 'No'));
        
        // Test authentication
        if (!$this->classroomService->authenticateWithFaculty($faculty)) {
            $this->error("Failed to authenticate with Google Classroom.");
            $this->info("The faculty member needs to log in through the web interface first to authorize access.");
            $this->info("Or the tokens may have expired and need to be refreshed.");
            return 1;
        }

        $this->info("✅ Authentication successful!");
        
        try {
            // Fetch courses
            $this->info("\n📚 Fetching Google Classroom courses...");
            $courses = $this->classroomService->getCourses();
            
            if (empty($courses)) {
                $this->warn("No active courses found.");
                return 0;
            }

            $this->info("Found " . count($courses) . " active courses:");
            
            foreach ($courses as $course) {
                $this->line("\n🎓 Course: {$course['name']}");
                $this->line("   ID: {$course['id']}");
                $this->line("   Section: " . ($course['section'] ?? 'No section'));
                $this->line("   Room: " . ($course['room'] ?? 'No room'));
                $this->line("   State: {$course['course_state']}");
                
                // Fetch students for this course
                $this->info("   👥 Fetching students...");
                try {
                    $students = $this->classroomService->getCourseStudents($course['id']);
                    $this->info("   Found " . count($students) . " students:");
                    
                    foreach ($students as $student) {
                        $this->line("      • {$student['profile']['name']} ({$student['profile']['email_address']})");
                    }
                    
                    // Fetch coursework
                    $this->info("   📝 Fetching coursework...");
                    $coursework = $this->classroomService->getCourseWork($course['id']);
                    $this->info("   Found " . count($coursework) . " assignments:");
                    
                    foreach ($coursework as $work) {
                        $maxPoints = $work['max_points'] ? " ({$work['max_points']} points)" : " (No points)";
                        $this->line("      • {$work['title']}{$maxPoints}");
                    }
                    
                } catch (\Exception $e) {
                    $this->error("   Failed to fetch course details: " . $e->getMessage());
                }
                
                $this->line("   " . str_repeat("-", 50));
            }
            
            $this->info("\n✅ Data fetch completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("Failed to fetch Google Classroom data: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
