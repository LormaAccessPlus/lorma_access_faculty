<?php

namespace Tests\Unit\Models;

use App\Models\SchoolFaculty;
use App\Models\SchoolSubjectAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSubjectAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test database connection for school_db using SQLite
        config([
            'database.connections.school_db' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]
        ]);
        
        // Create test tables for school database
        $this->createSchoolDatabaseTables();
    }

    protected function createSchoolDatabaseTables(): void
    {
        $connection = \DB::connection('school_db');
        
        // Create faculty table
        $connection->statement('
            CREATE TABLE faculty (
                faculty_id INTEGER PRIMARY KEY,
                faculty_code VARCHAR(50),
                first_name VARCHAR(100),
                middle_name VARCHAR(100),
                last_name VARCHAR(100),
                email VARCHAR(255),
                department_id INTEGER,
                status VARCHAR(20) DEFAULT "active"
            )
        ');

        // Create subject_assignments table
        $connection->statement('
            CREATE TABLE subject_assignments (
                assignment_id INTEGER PRIMARY KEY,
                faculty_id INTEGER,
                subject_id INTEGER,
                subject_code VARCHAR(20),
                subject_name VARCHAR(255),
                section VARCHAR(50),
                units INTEGER,
                schedule VARCHAR(255),
                room VARCHAR(100),
                academic_year INTEGER,
                semester INTEGER,
                status VARCHAR(20) DEFAULT "active"
            )
        ');
    }

    public function test_school_subject_assignment_uses_correct_connection(): void
    {
        $assignment = new SchoolSubjectAssignment();
        $this->assertEquals('school_db', $assignment->getConnectionName());
    }

    public function test_school_subject_assignment_uses_correct_table(): void
    {
        $assignment = new SchoolSubjectAssignment();
        $this->assertEquals('subject_assignments', $assignment->getTable());
    }

    public function test_school_subject_assignment_uses_correct_primary_key(): void
    {
        $assignment = new SchoolSubjectAssignment();
        $this->assertEquals('assignment_id', $assignment->getKeyName());
    }

    public function test_school_subject_assignment_does_not_use_timestamps(): void
    {
        $assignment = new SchoolSubjectAssignment();
        $this->assertFalse($assignment->usesTimestamps());
    }

    public function test_display_name_attribute(): void
    {
        $assignment = new SchoolSubjectAssignment([
            'subject_code' => 'CS101',
            'subject_name' => 'Introduction to Computer Science',
            'section' => 'A'
        ]);

        $this->assertEquals('CS101 - Introduction to Computer Science (A)', $assignment->display_name);
    }

    public function test_by_academic_year_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('subject_assignments')->insert([
            ['assignment_id' => 1, 'subject_code' => 'CS101', 'subject_name' => 'Subject 1', 'academic_year' => 2024, 'semester' => 1, 'status' => 'active'],
            ['assignment_id' => 2, 'subject_code' => 'CS102', 'subject_name' => 'Subject 2', 'academic_year' => 2023, 'semester' => 1, 'status' => 'active'],
        ]);

        $assignments = SchoolSubjectAssignment::byAcademicYear(2024)->get();
        $this->assertCount(1, $assignments);
        $this->assertEquals('CS101', $assignments->first()->subject_code);
    }

    public function test_by_semester_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('subject_assignments')->insert([
            ['assignment_id' => 1, 'subject_code' => 'CS101', 'subject_name' => 'Subject 1', 'academic_year' => 2024, 'semester' => 1, 'status' => 'active'],
            ['assignment_id' => 2, 'subject_code' => 'CS102', 'subject_name' => 'Subject 2', 'academic_year' => 2024, 'semester' => 2, 'status' => 'active'],
        ]);

        $assignments = SchoolSubjectAssignment::bySemester(1)->get();
        $this->assertCount(1, $assignments);
        $this->assertEquals('CS101', $assignments->first()->subject_code);
    }

    public function test_by_faculty_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('subject_assignments')->insert([
            ['assignment_id' => 1, 'faculty_id' => 1, 'subject_code' => 'CS101', 'subject_name' => 'Subject 1', 'status' => 'active'],
            ['assignment_id' => 2, 'faculty_id' => 2, 'subject_code' => 'CS102', 'subject_name' => 'Subject 2', 'status' => 'active'],
        ]);

        $assignments = SchoolSubjectAssignment::byFaculty(1)->get();
        $this->assertCount(1, $assignments);
        $this->assertEquals('CS101', $assignments->first()->subject_code);
    }

    public function test_current_scope(): void
    {
        // Set current academic year and semester
        config(['app.current_academic_year' => 2024]);
        config(['app.current_semester' => 1]);

        // Insert test data
        \DB::connection('school_db')->table('subject_assignments')->insert([
            ['assignment_id' => 1, 'subject_code' => 'CS101', 'subject_name' => 'Current Subject', 'academic_year' => 2024, 'semester' => 1, 'status' => 'active'],
            ['assignment_id' => 2, 'subject_code' => 'CS102', 'subject_name' => 'Past Subject', 'academic_year' => 2023, 'semester' => 2, 'status' => 'active'],
        ]);

        $assignments = SchoolSubjectAssignment::current()->get();
        $this->assertCount(1, $assignments);
        $this->assertEquals('Current Subject', $assignments->first()->subject_name);
    }

    public function test_active_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('subject_assignments')->insert([
            ['assignment_id' => 1, 'subject_code' => 'CS101', 'subject_name' => 'Active Subject', 'status' => 'active'],
            ['assignment_id' => 2, 'subject_code' => 'CS102', 'subject_name' => 'Inactive Subject', 'status' => 'inactive'],
        ]);

        $assignments = SchoolSubjectAssignment::active()->get();
        $this->assertCount(1, $assignments);
        $this->assertEquals('Active Subject', $assignments->first()->subject_name);
    }

    public function test_by_subject_code_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('subject_assignments')->insert([
            ['assignment_id' => 1, 'subject_code' => 'CS101', 'subject_name' => 'Subject 1', 'status' => 'active'],
            ['assignment_id' => 2, 'subject_code' => 'CS102', 'subject_name' => 'Subject 2', 'status' => 'active'],
        ]);

        $assignments = SchoolSubjectAssignment::bySubjectCode('CS101')->get();
        $this->assertCount(1, $assignments);
        $this->assertEquals('Subject 1', $assignments->first()->subject_name);
    }

    public function test_by_section_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('subject_assignments')->insert([
            ['assignment_id' => 1, 'subject_code' => 'CS101', 'subject_name' => 'Subject 1', 'section' => 'A', 'status' => 'active'],
            ['assignment_id' => 2, 'subject_code' => 'CS101', 'subject_name' => 'Subject 1', 'section' => 'B', 'status' => 'active'],
        ]);

        $assignments = SchoolSubjectAssignment::bySection('A')->get();
        $this->assertCount(1, $assignments);
        $this->assertEquals('A', $assignments->first()->section);
    }

    public function test_faculty_relationship(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('faculty')->insert([
            'faculty_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@lorma.edu',
            'status' => 'active'
        ]);

        \DB::connection('school_db')->table('subject_assignments')->insert([
            'assignment_id' => 1,
            'faculty_id' => 1,
            'subject_code' => 'CS101',
            'subject_name' => 'Introduction to Computer Science',
            'section' => 'A',
            'status' => 'active'
        ]);

        $assignment = SchoolSubjectAssignment::with('faculty')->find(1);
        
        $this->assertNotNull($assignment->faculty);
        $this->assertInstanceOf(SchoolFaculty::class, $assignment->faculty);
        $this->assertEquals('John', $assignment->faculty->first_name);
    }
}