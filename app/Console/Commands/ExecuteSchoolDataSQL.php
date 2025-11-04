<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExecuteSchoolDataSQL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:execute-sql {file=storage/app/gcr_school_data.sql : SQL file to execute}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute SQL file against the school database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sqlFile = $this->argument('file');
        
        if (!file_exists($sqlFile)) {
            $this->error("SQL file not found: {$sqlFile}");
            return 1;
        }

        $this->info("Executing SQL file: {$sqlFile}");
        
        if (!$this->confirm('This will modify the school database. Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        try {
            // Read the SQL file
            $sql = file_get_contents($sqlFile);
            
            // Split into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($statement) {
                    return !empty($statement) && !str_starts_with($statement, '--');
                }
            );

            $this->info("Found " . count($statements) . " SQL statements to execute.");
            
            // Execute each statement using the school database connection
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($statements as $index => $statement) {
                if (empty(trim($statement))) continue;
                
                try {
                    DB::connection('school_db')->statement($statement);
                    $successCount++;
                    
                    if (($index + 1) % 50 == 0) {
                        $this->info("Executed " . ($index + 1) . " statements...");
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->warn("Error executing statement " . ($index + 1) . ": " . $e->getMessage());
                    
                    if ($errorCount > 10) {
                        $this->error("Too many errors. Stopping execution.");
                        break;
                    }
                }
            }
            
            $this->info("✅ Execution completed!");
            $this->info("Successfully executed: {$successCount} statements");
            
            if ($errorCount > 0) {
                $this->warn("Errors encountered: {$errorCount} statements");
            }
            
            // Show summary of what was created
            $this->showSummary();
            
        } catch (\Exception $e) {
            $this->error("Failed to execute SQL file: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function showSummary()
    {
        try {
            $this->info("\n📊 Database Summary:");
            
            // Count students
            $studentCount = DB::connection('school_db')->table('studentdata')->count();
            $this->info("Students created: {$studentCount}");
            
            // Count subjects
            $subjectCount = DB::connection('school_db')->table('subject')->count();
            $this->info("Subjects created: {$subjectCount}");
            
            // Count schedules
            $scheduleCount = DB::connection('school_db')->table('schedule')->count();
            $this->info("Schedules created: {$scheduleCount}");
            
            // Count enrollments
            $enrollmentCount = DB::connection('school_db')->table('enrollment')->count();
            $this->info("Enrollments created: {$enrollmentCount}");
            
            // Count term grades
            $gradeCount = DB::connection('school_db')->table('termgrades')->count();
            $this->info("Grade records created: {$gradeCount}");
            
        } catch (\Exception $e) {
            $this->warn("Could not generate summary: " . $e->getMessage());
        }
    }
}
