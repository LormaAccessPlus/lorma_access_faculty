<?php

namespace Tests\Unit\Models;

use App\Models\SchoolStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolStudentTest extends TestCase
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
        
        // Create students table
        $connection->statement('
            CREATE TABLE students (
                student_id INTEGER PRIMARY KEY,
                student_number VARCHAR(50),
                first_name VARCHAR(100),
                middle_name VARCHAR(100),
                last_name VARCHAR(100),
                email VARCHAR(255),
                course_id INTEGER,
                year_level INTEGER,
                section VARCHAR(50),
                status VARCHAR(20) DEFAULT "active"
            )
        ');
    }

    public function test_school_student_uses_correct_connection(): void
    {
        $student = new SchoolStudent();
        $this->assertEquals('school_db', $student->getConnectionName());
    }

    public function test_school_student_uses_correct_table(): void
    {
        $student = new SchoolStudent();
        $this->assertEquals('students', $student->getTable());
    }

    public function test_school_student_uses_correct_primary_key(): void
    {
        $student = new SchoolStudent();
        $this->assertEquals('student_id', $student->getKeyName());
    }

    public function test_school_student_does_not_use_timestamps(): void
    {
        $student = new SchoolStudent();
        $this->assertFalse($student->usesTimestamps());
    }

    public function test_full_name_attribute(): void
    {
        $student = new SchoolStudent([
            'first_name' => 'Jane',
            'middle_name' => 'B.',
            'last_name' => 'Smith'
        ]);

        $this->assertEquals('Jane B. Smith', $student->full_name);
    }

    public function test_full_name_attribute_with_empty_middle_name(): void
    {
        $student = new SchoolStudent([
            'first_name' => 'Jane',
            'middle_name' => '',
            'last_name' => 'Smith'
        ]);

        $this->assertEquals('Jane Smith', $student->full_name);
    }

    public function test_active_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('students')->insert([
            ['student_id' => 1, 'student_number' => '2024001', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@student.lorma.edu', 'status' => 'active'],
            ['student_id' => 2, 'student_number' => '2024002', 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@student.lorma.edu', 'status' => 'inactive'],
        ]);

        $activeStudents = SchoolStudent::active()->get();
        $this->assertCount(1, $activeStudents);
        $this->assertEquals('John', $activeStudents->first()->first_name);
    }

    public function test_by_student_number_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('students')->insert([
            ['student_id' => 1, 'student_number' => '2024001', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@student.lorma.edu', 'status' => 'active'],
            ['student_id' => 2, 'student_number' => '2024002', 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@student.lorma.edu', 'status' => 'active'],
        ]);

        $student = SchoolStudent::byStudentNumber('2024001')->first();
        $this->assertNotNull($student);
        $this->assertEquals('John', $student->first_name);
    }

    public function test_by_email_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('students')->insert([
            ['student_id' => 1, 'student_number' => '2024001', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@student.lorma.edu', 'status' => 'active'],
            ['student_id' => 2, 'student_number' => '2024002', 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@student.lorma.edu', 'status' => 'active'],
        ]);

        $student = SchoolStudent::byEmail('john.doe@student.lorma.edu')->first();
        $this->assertNotNull($student);
        $this->assertEquals('John', $student->first_name);
    }

    public function test_by_course_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('students')->insert([
            ['student_id' => 1, 'student_number' => '2024001', 'first_name' => 'John', 'last_name' => 'Doe', 'course_id' => 1, 'status' => 'active'],
            ['student_id' => 2, 'student_number' => '2024002', 'first_name' => 'Jane', 'last_name' => 'Smith', 'course_id' => 2, 'status' => 'active'],
        ]);

        $students = SchoolStudent::byCourse(1)->get();
        $this->assertCount(1, $students);
        $this->assertEquals('John', $students->first()->first_name);
    }

    public function test_by_year_level_scope(): void
    {
        // Insert test data
        \DB::connection('school_db')->table('students')->insert([
            ['student_id' => 1, 'student_number' => '2024001', 'first_name' => 'John', 'last_name' => 'Doe', 'year_level' => 1, 'status' => 'active'],
            ['student_id' => 2, 'student_number' => '2024002', 'first_name' => 'Jane', 'last_name' => 'Smith', 'year_level' => 2, 'status' => 'active'],
        ]);

        $students = SchoolStudent::byYearLevel(1)->get();
        $this->assertCount(1, $students);
        $this->assertEquals('John', $students->first()->first_name);
    }
}