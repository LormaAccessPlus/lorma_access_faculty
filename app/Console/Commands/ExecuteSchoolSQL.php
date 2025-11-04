<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExecuteSchoolSQL extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'school:execute-sql {file} {--connection=school_db} {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Execute SQL file against the school database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $connection = $this->option('connection');
        $force = $this->option('force');

        // Check if file exists
        if (!File::exists($filePath)) {
            $this->error("SQL file not found: {$filePath}");
            return 1;
        }

        // Show file info
        $fileSize = File::size($filePath);
        $this->info("SQL File: {$filePath}");
        $this->info("File Size: " . number_format($fileSize) . " bytes");
        $this->info("Target Connection: {$connection}");

        // Read and preview the file
        $sqlContent = File::get($filePath);
        $lines = explode("\n", $sqlContent);
        $totalLines = count($lines);

        $this->info("Total Lines: {$totalLines}");

        // Show preview of first few lines
        $this->line("\n--- SQL Preview (first 10 lines) ---");
        for ($i = 0; $i < min(10, $totalLines); $i++) {
            $this->line(($i + 1) . ": " . trim($lines[$i]));
        }
        $this->line("...");

        // Safety confirmation
        if (!$force) {
            $this->warn("\n⚠️  WARNING: This will execute SQL against the {$connection} database!");
            $this->warn("This may modify or delete existing data.");
            
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        try {
            // Test database connection
            $this->info("\n🔍 Testing database connection...");
            DB::connection($connection)->getPdo();
            $this->info("✅ Database connection successful");

            // Execute SQL
            $this->info("\n🚀 Executing SQL file...");
            $startTime = microtime(true);

            // Split SQL into individual statements
            $statements = $this->splitSqlStatements($sqlContent);
            $totalStatements = count($statements);
            
            $this->info("Found {$totalStatements} SQL statements to execute");

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            $progressBar = $this->output->createProgressBar($totalStatements);
            $progressBar->start();

            foreach ($statements as $index => $statement) {
                $statement = trim($statement);
                
                // Skip empty statements and comments
                if (empty($statement) || 
                    str_starts_with($statement, '--') || 
                    str_starts_with($statement, '/*') ||
                    str_starts_with($statement, 'SET FOREIGN_KEY_CHECKS')) {
                    $progressBar->advance();
                    continue;
                }

                try {
                    DB::connection($connection)->statement($statement);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'statement' => substr($statement, 0, 100) . '...',
                        'error' => $e->getMessage()
                    ];
                    
                    // Stop on critical errors
                    if (str_contains($e->getMessage(), 'syntax error') || 
                        str_contains($e->getMessage(), 'doesn\'t exist')) {
                        $this->error("\n❌ Critical error encountered, stopping execution");
                        break;
                    }
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            // Show results
            $this->line("\n\n📊 Execution Results:");
            $this->info("✅ Successful statements: {$successCount}");
            
            if ($errorCount > 0) {
                $this->warn("⚠️  Failed statements: {$errorCount}");
                
                if (count($errors) > 0) {
                    $this->line("\n🔍 Error Details:");
                    foreach (array_slice($errors, 0, 5) as $error) {
                        $this->error("Statement: " . $error['statement']);
                        $this->error("Error: " . $error['error']);
                        $this->line("");
                    }
                    
                    if (count($errors) > 5) {
                        $this->warn("... and " . (count($errors) - 5) . " more errors");
                    }
                }
            }
            
            $this->info("⏱️  Execution time: {$executionTime} seconds");

            // Verify some key data
            $this->verifyData($connection);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Database error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Split SQL content into individual statements
     */
    private function splitSqlStatements(string $sql): array
    {
        // Remove comments and normalize line endings
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);

        // Split by semicolon (simple approach)
        $statements = explode(';', $sql);
        
        // Filter out empty statements
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }

    /**
     * Verify that data was inserted correctly
     */
    private function verifyData(string $connection): void
    {
        $this->line("\n🔍 Verifying data insertion...");

        try {
            // Check teachers
            $teacherCount = DB::connection($connection)->table('teacher')->count();
            $this->info("Teachers: {$teacherCount}");

            // Check subjects
            $subjectCount = DB::connection($connection)->table('subject')->count();
            $this->info("Subjects: {$subjectCount}");

            // Check students
            $studentCount = DB::connection($connection)->table('studentdata')->count();
            $this->info("Students: {$studentCount}");

            // Check schedules
            $scheduleCount = DB::connection($connection)->table('schedule')->count();
            $this->info("Schedules: {$scheduleCount}");

            // Check enrollments
            $enrollmentCount = DB::connection($connection)->table('enrollment')->count();
            $this->info("Enrollments: {$enrollmentCount}");

            // Check term grades
            $gradeCount = DB::connection($connection)->table('termgrades')->count();
            $this->info("Term Grades: {$gradeCount}");

            $this->info("✅ Data verification complete");

        } catch (\Exception $e) {
            $this->warn("⚠️  Could not verify data: " . $e->getMessage());
        }
    }
}