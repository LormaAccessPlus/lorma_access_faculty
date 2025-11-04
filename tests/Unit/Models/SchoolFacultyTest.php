<?php

namespace Tests\Unit\Models;

use App\Models\SchoolFaculty;
use App\Models\SchoolSubjectAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolFacultyTest extends TestCase
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

    public function test_school_faculty_uses_correct_connection(): void
    {
        $faculty = new SchoolFaculty();
        $this->assertEquals('school_db', $faculty->getConnectionName());
    }

    public function test_school_faculty_uses_correct_table(): void
    {
        $faculty = new SchoolFaculty();
        $this->assertEquals('faculty', $faculty->getTable());
    }

    public function test_school_faculty_uses_correct_primary_key(): void
    {
        $faculty = new SchoolFaculty();
        $this->assertEquals('faculty_id', $faculty->getKeyName());
    }

    public function test_school_faculty_does_not_use_timestamps(): void
    {
        $faculty = new SchoolFaculty();
        $this->assertFalse($faculty->usesTimestamps());
    }

    public function test_full_name_attribute(): void
    {
        $faculty = new SchoolFaculty([
            'first_name' => 'John',
            'middle_name' => 'A.',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John A. Doe', $faculty->full_name);
    }

    public function test_full_name_attribute_with_empty_middle_name(): void
    {
        $faculty = new SchoolFaculty([
            'first_name' => 'John',
            'middle_name' => '',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $faculty->full_name);
    }

    public function test_full_name_attribute_handles_extra_spaces(): void
    {
        $faculty = new SchoolFaculty([
            'first_name' => 'John  ',
            'middle_name' => '  A.  ',
            'last_name' => '  Doe'
        ]);

        $this->assertEquals('John A. Doe', $faculty->full_name);
    }

    public function test_active_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('faculty')->insert([
            ['faculty_id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@lorma.edu', 'status' => 'active'],
            ['faculty_id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@lorma.edu', 'status' => 'inactive'],
        ]);

        $activeFaculty = SchoolFaculty::active()->get();
        $this->assertCount(1, $activeFaculty);
        $this->assertEquals('John', $activeFaculty->first()->first_name);
    }

    public function test_by_email_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('faculty')->insert([
            ['faculty_id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@lorma.edu', 'status' => 'active'],
            ['faculty_id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@lorma.edu', 'status' => 'active'],
        ]);

        $faculty = SchoolFaculty::byEmail('john@lorma.edu')->first();
        $this->assertNotNull($faculty);
        $this->assertEquals('John', $faculty->first_name);
    }

    public function test_subject_assignments_relationship(): void
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
            [
                'assignment_id' => 1,
                'faculty_id' => 1,
                'subject_code' => 'CS101',
                'subject_name' => 'Introduction to Computer Science',
                'section' => 'A',
                'academic_year' => 2024,
                'semester' => 1,
                'status' => 'active'
            ],
            [
                'assignment_id' => 2,
                'faculty_id' => 1,
                'subject_code' => 'CS102',
                'subject_name' => 'Programming Fundamentals',
                'section' => 'B',
                'academic_year' => 2024,
                'semester' => 1,
                'status' => 'active'
            ]
        ]);

        $faculty = SchoolFaculty::find(1);
        $assignments = $faculty->subjectAssignments;
        
        $this->assertCount(2, $assignments);
        $this->assertInstanceOf(SchoolSubjectAssignment::class, $assignments->first());
    }

    public function test_current_subject_assignments_relationship(): void
    {
        // Set current academic year and semester
        config(['app.current_academic_year' => 2024]);
        config(['app.current_semester' => 1]);

        // Insert test data
        \DB::connection('school_db')->table('faculty')->insert([
            'faculty_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@lorma.edu',
            'status' => 'active'
        ]);

        \DB::connection('school_db')->table('subject_assignments')->insert([
            [
                'assignment_id' => 1,
                'faculty_id' => 1,
                'subject_code' => 'CS101',
                'subject_name' => 'Current Subject',
                'section' => 'A',
                'academic_year' => 2024,
                'semester' => 1,
                'status' => 'active'
            ],
            [
                'assignment_id' => 2,
                'faculty_id' => 1,
                'subject_code' => 'CS102',
                'subject_name' => 'Past Subject',
                'section' => 'B',
                'academic_year' => 2023,
                'semester' => 2,
                'status' => 'active'
            ]
        ]);

        $faculty = SchoolFaculty::find(1);
        $currentAssignments = $faculty->currentSubjectAssignments;
        
        $this->assertCount(1, $currentAssignments);
        $this->assertEquals('Current Subject', $currentAssignments->first()->subject_name);
    }
}