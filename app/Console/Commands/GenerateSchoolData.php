<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\SchoolFaculty;
use App\Models\SchoolSubjectAssignment;
use App\Models\SchoolStudent;

class GenerateSchoolData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:generate-data {--clean : Clean existing data first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and insert school database data from faculty grading system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clean')) {
            $this->cleanExistingData();
        }

        $this->info('Generating school database data from faculty grading system...');
        
        $sqlFile = storage_path('app/school_data_final.sql');
        $sql = "-- Final School Database Data\n";
        $sql .= "-- Generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        // Process faculty and subjects together to maintain consistency
        $faculties = Faculty::with('subjects.studentMappings')->get();
        $facultyMapping = [];
        
        foreach ($faculties as $faculty) {
            // Generate consistent faculty code
            $facultyCode = $this->generateConsistentFacultyCode($faculty->email);
            $facultyMapping[$faculty->id] = $facultyCode;
            
            $nameParts = $this->parseFullName($faculty->name);
            
            $sql .= "-- Faculty: {$faculty->name}\n";
            $sql .= "INSERT IGNORE INTO faculty (faculty_code, first_name, middle_name, last_name, email, status) VALUES (\n";
            $sql .= "    '{$facultyCode}',\n";
            $sql .= "    " . $this->sqlEscape($nameParts['first_name']) . ",\n";
            $sql .= "    " . $this->sqlEscape($nameParts['middle_name']) . ",\n";
            $sql .= "    " . $this->sqlEscape($nameParts['last_name']) . ",\n";
            $sql .= "    " . $this->sqlEscape($faculty->email) . ",\n";
            $sql .= "    'active'\n";
            $sql .= ");\n\n";
            
            // Process subjects for this faculty
            foreach ($faculty->subjects as $subject) {
                $sql .= "-- Subject: {$subject->subject_code} - {$subject->subject_name}\n";
                $sql .= "INSERT IGNORE INTO subject_assignments (faculty_id, subject_code, subject_name, section, academic_year, semester, status) \n";
                $sql .= "SELECT f.faculty_id, \n";
                $sql .= "    " . $this->sqlEscape($subject->subject_code) . ",\n";
                $sql .= "    " . $this->sqlEscape($subject->subject_name) . ",\n";
                $sql .= "    " . $this->sqlEscape($subject->section) . ",\n";
                $sql .= "    " . $this->extractAcademicYear($subject->academic_year) . ",\n";
                $sql .= "    " . $this->extractSemester($subject->semester) . ",\n";
                $sql .= "    'active'\n";
                $sql .= "FROM faculty f WHERE f.faculty_code = '{$facultyCode}';\n\n";
                
                // Process students for this subject
                foreach ($subject->studentMappings as $mapping) {
                    if ($mapping->student_name && $mapping->student_email) {
                        $nameParts = $this->parseFullName($mapping->student_name);
                        $studentNumber = $this->generateStudentNumber($mapping->student_email);
                        
                        $sql .= "-- Student: {$mapping->student_name}\n";
                        $sql .= "INSERT IGNORE INTO students (student_number, first_name, middle_name, last_name, email, status) VALUES (\n";
                        $sql .= "    '{$studentNumber}',\n";
                        $sql .= "    " . $this->sqlEscape($nameParts['first_name']) . ",\n";
                        $sql .= "    " . $this->sqlEscape($nameParts['middle_name']) . ",\n";
                        $sql .= "    " . $this->sqlEscape($nameParts['last_name']) . ",\n";
                        $sql .= "    " . $this->sqlEscape($mapping->student_email) . ",\n";
                        $sql .= "    'active'\n";
                        $sql .= ");\n\n";
                        
                        $sql .= "-- Enrollment for {$mapping->student_name} in {$subject->subject_code}\n";
                        $sql .= "INSERT IGNORE INTO enrollments (student_id, assignment_id, status)\n";
                        $sql .= "SELECT s.student_id, sa.assignment_id, 'active'\n";
                        $sql .= "FROM students s \n";
                        $sql .= "JOIN subject_assignments sa ON sa.subject_code = " . $this->sqlEscape($subject->subject_code) . "\n";
                        $sql .= "  AND sa.section = " . $this->sqlEscape($subject->section) . "\n";
                        $sql .= "JOIN faculty f ON f.faculty_id = sa.faculty_id AND f.faculty_code = '{$facultyCode}'\n";
                        $sql .= "WHERE s.student_number = '{$studentNumber}';\n\n";
                    }
                }
            }
        }
        
        // Write to file
        file_put_contents($sqlFile, $sql);
        $this->info("SQL file generated: {$sqlFile}");
        
        // Execute the SQL
        if ($this->confirm('Execute the SQL against the school database?')) {
            $this->executeSqlFile($sqlFile);
            $this->showResults();
        }
    }

    private function cleanExistingData()
    {
        $this->info('Cleaning existing school database data...');
        
        $connection = DB::connection('school_db');
        
        $connection->statement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->statement('TRUNCATE TABLE enrollments');
        $connection->statement('TRUNCATE TABLE subject_assignments');
        $connection->statement('TRUNCATE TABLE students');
        $connection->statement('TRUNCATE TABLE faculty');
        $connection->statement('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->info('Existing data cleaned.');
    }

    private function executeSqlFile($sqlFile)
    {
        $this->info('Executing SQL...');
        
        $sql = file_get_contents($sqlFile);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $connection = DB::connection('school_db');
        $executed = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $connection->statement($statement);
                $executed++;
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
        }
        
        $this->info("Executed {$executed} statements.");
    }

    private function showResults()
    {
        $this->info('School Database Summary:');
        
        $facultyCount = SchoolFaculty::count();
        $studentCount = SchoolStudent::count();
        $assignmentCount = SchoolSubjectAssignment::count();
        $enrollmentCount = DB::connection('school_db')->table('enrollments')->count();
        
        $this->line("Faculty: {$facultyCount}");
        $this->line("Students: {$studentCount}");
        $this->line("Subject Assignments: {$assignmentCount}");
        $this->line("Enrollments: {$enrollmentCount}");
        
        $this->info('Sample Data:');
        $faculty = SchoolFaculty::first();
        if ($faculty) {
            $this->line("Faculty: {$faculty->faculty_code} - {$faculty->first_name} {$faculty->last_name}");
        }
        
        $assignment = SchoolSubjectAssignment::with('faculty')->first();
        if ($assignment) {
            $this->line("Assignment: {$assignment->subject_code} {$assignment->section} - {$assignment->faculty->first_name} {$assignment->faculty->last_name}");
        }
    }

    private function generateConsistentFacultyCode($email)
    {
        // Generate consistent code based on email
        $hash = substr(md5($email), 0, 6);
        return 'FAC' . strtoupper($hash);
    }

    private function generateStudentNumber($email)
    {
        // Generate consistent student number based on email
        $hash = substr(md5($email), 0, 8);
        return '2024' . strtoupper($hash);
    }

    private function parseFullName($fullName)
    {
        $parts = array_filter(explode(' ', trim($fullName)));
        
        if (count($parts) == 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => ''
            ];
        } elseif (count($parts) == 2) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[1]
            ];
        } else {
            return [
                'first_name' => $parts[0],
                'middle_name' => $parts[1],
                'last_name' => implode(' ', array_slice($parts, 2))
            ];
        }
    }

    private function extractAcademicYear($academicYear)
    {
        if (preg_match('/(\d{4})/', $academicYear, $matches)) {
            return (int)$matches[1];
        }
        return date('Y');
    }

    private function extractSemester($semester)
    {
        if (preg_match('/(\d+)/', $semester, $matches)) {
            return (int)$matches[1];
        }
        return 1;
    }

    private function sqlEscape($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        return "'" . addslashes($value) . "'";
    }
}
