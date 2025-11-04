<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupSchoolDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:setup {--force : Force recreation of tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the school database tables and generate SQL inserts from Google Classroom data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up school database...');
        
        // Create school database tables
        $this->createSchoolTables();
        
        // Generate SQL inserts from current faculty grading system data
        $this->generateSqlInserts();
        
        $this->info('School database setup completed!');
    }

    /**
     * Create the school database tables
     */
    private function createSchoolTables()
    {
        $connection = DB::connection('school_db');
        
        $this->info('Creating school database tables...');
        
        // Create faculty table
        $connection->statement("
            CREATE TABLE IF NOT EXISTS faculty (
                faculty_id INT AUTO_INCREMENT PRIMARY KEY,
                faculty_code VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100),
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                department_id INT,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create students table
        $connection->statement("
            CREATE TABLE IF NOT EXISTS students (
                student_id INT AUTO_INCREMENT PRIMARY KEY,
                student_number VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100),
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                course_id INT,
                year_level INT DEFAULT 1,
                section VARCHAR(10),
                status ENUM('active', 'inactive', 'graduated', 'dropped') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create subject_assignments table
        $connection->statement("
            CREATE TABLE IF NOT EXISTS subject_assignments (
                assignment_id INT AUTO_INCREMENT PRIMARY KEY,
                faculty_id INT NOT NULL,
                subject_id INT,
                subject_code VARCHAR(20) NOT NULL,
                subject_name VARCHAR(255) NOT NULL,
                section VARCHAR(10) NOT NULL,
                units INT DEFAULT 3,
                schedule TEXT,
                room VARCHAR(50),
                academic_year INT NOT NULL,
                semester INT NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE,
                INDEX idx_faculty_year_sem (faculty_id, academic_year, semester),
                INDEX idx_subject_code (subject_code),
                INDEX idx_status (status)
            )
        ");
        
        // Create enrollments table (linking students to subject assignments)
        $connection->statement("
            CREATE TABLE IF NOT EXISTS enrollments (
                enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                assignment_id INT NOT NULL,
                enrollment_date DATE DEFAULT (CURRENT_DATE),
                status ENUM('active', 'dropped', 'completed') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                FOREIGN KEY (assignment_id) REFERENCES subject_assignments(assignment_id) ON DELETE CASCADE,
                UNIQUE KEY unique_enrollment (student_id, assignment_id)
            )
        ");
        
        $this->info('School database tables created successfully!');
    }

    /**
     * Generate SQL inserts from current faculty grading system data
     */
    private function generateSqlInserts()
    {
        $this->info('Generating SQL inserts from faculty grading system data...');
        
        $sqlFile = storage_path('app/school_database_inserts.sql');
        $sql = "-- School Database Inserts Generated from Faculty Grading System\n";
        $sql .= "-- Generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        // Get current faculty grading system data
        $faculties = \App\Models\Faculty::all();
        $subjects = \App\Models\Subject::with(['faculty', 'studentMappings'])->get();
        
        $sql .= "-- Faculty Data\n";
        foreach ($faculties as $faculty) {
            $nameParts = $this->parseFullName($faculty->name);
            $facultyCode = $this->generateFacultyCode($faculty->name);
            
            $sql .= "INSERT IGNORE INTO faculty (faculty_code, first_name, middle_name, last_name, email, status) VALUES (\n";
            $sql .= "    '{$facultyCode}',\n";
            $sql .= "    " . $this->sqlEscape($nameParts['first_name']) . ",\n";
            $sql .= "    " . $this->sqlEscape($nameParts['middle_name']) . ",\n";
            $sql .= "    " . $this->sqlEscape($nameParts['last_name']) . ",\n";
            $sql .= "    " . $this->sqlEscape($faculty->email) . ",\n";
            $sql .= "    'active'\n";
            $sql .= ");\n\n";
        }
        
        $sql .= "-- Subject Assignments Data\n";
        foreach ($subjects as $subject) {
            $facultyCode = $this->generateFacultyCode($subject->faculty->name);
            
            $sql .= "INSERT IGNORE INTO subject_assignments (faculty_id, subject_code, subject_name, section, academic_year, semester, status) \n";
            $sql .= "SELECT f.faculty_id, \n";
            $sql .= "    " . $this->sqlEscape($subject->subject_code) . ",\n";
            $sql .= "    " . $this->sqlEscape($subject->subject_name) . ",\n";
            $sql .= "    " . $this->sqlEscape($subject->section) . ",\n";
            $sql .= "    " . $this->extractAcademicYear($subject->academic_year) . ",\n";
            $sql .= "    " . $this->extractSemester($subject->semester) . ",\n";
            $sql .= "    'active'\n";
            $sql .= "FROM faculty f WHERE f.faculty_code = '{$facultyCode}';\n\n";
        }
        
        $sql .= "-- Student Data and Enrollments\n";
        foreach ($subjects as $subject) {
            $facultyCode = $this->generateFacultyCode($subject->faculty->name);
            
            foreach ($subject->studentMappings as $mapping) {
                if ($mapping->student_name && $mapping->student_email) {
                    $nameParts = $this->parseFullName($mapping->student_name);
                    $studentNumber = $this->generateStudentNumber($mapping->student_name);
                    
                    // Insert student
                    $sql .= "INSERT IGNORE INTO students (student_number, first_name, middle_name, last_name, email, status) VALUES (\n";
                    $sql .= "    '{$studentNumber}',\n";
                    $sql .= "    " . $this->sqlEscape($nameParts['first_name']) . ",\n";
                    $sql .= "    " . $this->sqlEscape($nameParts['middle_name']) . ",\n";
                    $sql .= "    " . $this->sqlEscape($nameParts['last_name']) . ",\n";
                    $sql .= "    " . $this->sqlEscape($mapping->student_email) . ",\n";
                    $sql .= "    'active'\n";
                    $sql .= ");\n\n";
                    
                    // Insert enrollment
                    $sql .= "INSERT IGNORE INTO enrollments (student_id, assignment_id, status)\n";
                    $sql .= "SELECT s.student_id, sa.assignment_id, 'active'\n";
                    $sql .= "FROM students s, subject_assignments sa, faculty f\n";
                    $sql .= "WHERE s.student_number = '{$studentNumber}'\n";
                    $sql .= "  AND sa.subject_code = " . $this->sqlEscape($subject->subject_code) . "\n";
                    $sql .= "  AND sa.section = " . $this->sqlEscape($subject->section) . "\n";
                    $sql .= "  AND sa.faculty_id = f.faculty_id\n";
                    $sql .= "  AND f.faculty_code = '{$facultyCode}';\n\n";
                }
            }
        }
        
        // Write SQL to file
        file_put_contents($sqlFile, $sql);
        
        $this->info("SQL inserts generated and saved to: {$sqlFile}");
        
        // Ask if user wants to execute the SQL
        if ($this->confirm('Do you want to execute the SQL inserts against the school database?')) {
            $this->executeSqlFile($sqlFile);
        }
    }

    /**
     * Execute the SQL file against the school database
     */
    private function executeSqlFile($sqlFile)
    {
        $this->info('Executing SQL inserts...');
        
        $sql = file_get_contents($sqlFile);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $connection = DB::connection('school_db');
        $executed = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $connection->statement($statement);
                $executed++;
            } catch (\Exception $e) {
                $this->error("Error executing statement: " . $e->getMessage());
                $this->line("Statement: " . substr($statement, 0, 100) . "...");
                $errors++;
            }
        }
        
        $this->info("Executed {$executed} statements successfully.");
        if ($errors > 0) {
            $this->warn("Encountered {$errors} errors.");
        }
    }

    /**
     * Parse full name into components
     */
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

    /**
     * Generate faculty code from name
     */
    private function generateFacultyCode($name)
    {
        $parts = explode(' ', strtoupper(trim($name)));
        $code = '';
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $code .= substr($part, 0, 1);
            }
        }
        
        return $code . rand(100, 999);
    }

    /**
     * Generate student number from name
     */
    private function generateStudentNumber($name)
    {
        $hash = substr(md5($name), 0, 6);
        return '2024' . strtoupper($hash);
    }

    /**
     * Extract academic year from string
     */
    private function extractAcademicYear($academicYear)
    {
        if (preg_match('/(\d{4})/', $academicYear, $matches)) {
            return (int)$matches[1];
        }
        return date('Y');
    }

    /**
     * Extract semester from string
     */
    private function extractSemester($semester)
    {
        if (preg_match('/(\d+)/', $semester, $matches)) {
            return (int)$matches[1];
        }
        return 1;
    }

    /**
     * Escape SQL string
     */
    private function sqlEscape($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        return "'" . addslashes($value) . "'";
    }
}
