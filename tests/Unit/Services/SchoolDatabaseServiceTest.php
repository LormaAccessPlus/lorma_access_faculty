<?php

namespace Tests\Unit\Services;

use App\Models\SchoolFaculty;
use App\Models\SchoolStudent;
use App\Models\SchoolSubjectAssignment;
use App\Services\SchoolDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SchoolDatabaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SchoolDatabaseService $service;

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
        
        $this->service = new SchoolDatabaseService();
    }

    protected function createSchoolDatabaseTables(): void
    {
        $connection = DB::connection('school_db');
        
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

        // Create enrollments table for student-assignment relationship
        $connection->statement('
            CREATE TABLE enrollments (
                enrollment_id INTEGER PRIMARY KEY,
                student_id INTEGER,
                assignment_id INTEGER,
                enrollment_date DATE,
                status VARCHAR(20) DEFAULT "active"
            )
        ');
    }

    protected function seedTestData(): void
    {
        $connection = DB::connection('school_db');
        
        // Insert faculty
        $connection->table('faculty')->insert([
            [
                'faculty_id' => 1,
                'faculty_code' => 'FAC001',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@lorma.edu',
                'status' => 'active'
            ],
            [
                'faculty_id' => 2,
                'faculty_code' => 'FAC002',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@lorma.edu',
                'status' => 'inactive'
            ]
        ]);

        // Insert students
        $connection->table('students')->insert([
            [
                'student_id' => 1,
                'student_number' => '2024001',
                'first_name' => 'Alice',
                'last_name' => 'Johnson',
                'email' => 'alice.johnson@student.lorma.edu',
                'course_id' => 1,
                'year_level' => 1,
                'status' => 'active'
            ],
            [
                'student_id' => 2,
                'student_number' => '2024002',
                'first_name' => 'Bob',
                'last_name' => 'Wilson',
                'email' => 'bob.wilson@student.lorma.edu',
                'course_id' => 1,
                'year_level' => 1,
                'status' => 'active'
            ]
        ]);

        // Insert subject assignments
        $connection->table('subject_assignments')->insert([
            [
                'assignment_id' => 1,
                'faculty_id' => 1,
                'subject_id' => 101,
                'subject_code' => 'CS101',
                'subject_name' => 'Introduction to Computer Science',
                'section' => 'A',
                'units' => 3,
                'academic_year' => 2024,
                'semester' => 1,
                'status' => 'active'
            ],
            [
                'assignment_id' => 2,
                'faculty_id' => 1,
                'subject_id' => 102,
                'subject_code' => 'CS102',
                'subject_name' => 'Programming Fundamentals',
                'section' => 'B',
                'units' => 3,
                'academic_year' => 2024,
                'semester' => 1,
                'status' => 'active'
            ],
            [
                'assignment_id' => 3,
                'faculty_id' => 1,
                'subject_id' => 103,
                'subject_code' => 'CS103',
                'subject_name' => 'Data Structures',
                'section' => 'A',
                'units' => 3,
                'academic_year' => 2023,
                'semester' => 2,
                'status' => 'active'
            ]
        ]);

        // Insert enrollments
        $connection->table('enrollments')->insert([
            ['enrollment_id' => 1, 'student_id' => 1, 'assignment_id' => 1, 'status' => 'active'],
            ['enrollment_id' => 2, 'student_id' => 2, 'assignment_id' => 1, 'status' => 'active'],
            ['enrollment_id' => 3, 'student_id' => 1, 'assignment_id' => 2, 'status' => 'active'],
        ]);
    }

    public function test_connection_is_established(): void
    {
        $this->assertTrue($this->service->testConnection());
    }

    public function test_find_faculty_by_email(): void
    {
        $this->seedTestData();

        $faculty = $this->service->findFacultyByEmail('john.doe@lorma.edu');
        
        $this->assertNotNull($faculty);
        $this->assertInstanceOf(SchoolFaculty::class, $faculty);
        $this->assertEquals('John', $faculty->first_name);
        $this->assertEquals('Doe', $faculty->last_name);
    }

    public function test_find_faculty_by_email_returns_null_for_inactive(): void
    {
        $this->seedTestData();

        $faculty = $this->service->findFacultyByEmail('jane.smith@lorma.edu');
        
        $this->assertNull($faculty);
    }

    public function test_find_faculty_by_email_returns_null_for_nonexistent(): void
    {
        $this->seedTestData();

        $faculty = $this->service->findFacultyByEmail('nonexistent@lorma.edu');
        
        $this->assertNull($faculty);
    }

    public function test_get_current_faculty_assignments(): void
    {
        config(['app.current_academic_year' => 2024]);
        config(['app.current_semester' => 1]);
        
        $this->seedTestData();

        $assignments = $this->service->getCurrentFacultyAssignments(1);
        
        $this->assertCount(2, $assignments);
        $this->assertEquals('CS101', $assignments->first()->subject_code);
        $this->assertEquals('CS102', $assignments->last()->subject_code);
    }

    public function test_get_faculty_assignment_history(): void
    {
        $this->seedTestData();

        $assignments = $this->service->getFacultyAssignmentHistory(1);
        
        $this->assertCount(3, $assignments);
        // Should be ordered by academic year desc, semester desc
        $this->assertEquals('CS101', $assignments->first()->subject_code);
    }

    public function test_get_faculty_assignments_by_period(): void
    {
        $this->seedTestData();

        $assignments = $this->service->getFacultyAssignmentsByPeriod(1, 2023, 2);
        
        $this->assertCount(1, $assignments);
        $this->assertEquals('CS103', $assignments->first()->subject_code);
    }

    public function test_find_student_by_email(): void
    {
        $this->seedTestData();

        $student = $this->service->findStudentByEmail('alice.johnson@student.lorma.edu');
        
        $this->assertNotNull($student);
        $this->assertInstanceOf(SchoolStudent::class, $student);
        $this->assertEquals('Alice', $student->first_name);
        $this->assertEquals('Johnson', $student->last_name);
    }

    public function test_find_student_by_number(): void
    {
        $this->seedTestData();

        $student = $this->service->findStudentByNumber('2024001');
        
        $this->assertNotNull($student);
        $this->assertInstanceOf(SchoolStudent::class, $student);
        $this->assertEquals('Alice', $student->first_name);
    }

    public function test_get_available_academic_years(): void
    {
        $this->seedTestData();

        $years = $this->service->getAvailableAcademicYears();
        
        $this->assertCount(2, $years);
        $this->assertTrue($years->contains(2024));
        $this->assertTrue($years->contains(2023));
        // Should be ordered desc
        $this->assertEquals(2024, $years->first());
    }

    public function test_get_available_semesters(): void
    {
        $this->seedTestData();

        $semesters = $this->service->getAvailableSemesters(2024);
        
        $this->assertCount(1, $semesters);
        $this->assertTrue($semesters->contains(1));
    }

    public function test_get_subject_assignment(): void
    {
        $this->seedTestData();

        $assignment = $this->service->getSubjectAssignment(1);
        
        $this->assertNotNull($assignment);
        $this->assertInstanceOf(SchoolSubjectAssignment::class, $assignment);
        $this->assertEquals('CS101', $assignment->subject_code);
        $this->assertNotNull($assignment->faculty);
    }

    public function test_search_students(): void
    {
        $this->seedTestData();

        $students = $this->service->searchStudents('Alice');
        
        $this->assertCount(1, $students);
        $this->assertEquals('Alice', $students->first()->first_name);
    }

    public function test_search_students_by_email(): void
    {
        $this->seedTestData();

        $students = $this->service->searchStudents('alice.johnson');
        
        $this->assertCount(1, $students);
        $this->assertEquals('Alice', $students->first()->first_name);
    }

    public function test_search_students_by_student_number(): void
    {
        $this->seedTestData();

        $students = $this->service->searchStudents('2024001');
        
        $this->assertCount(1, $students);
        $this->assertEquals('Alice', $students->first()->first_name);
    }

    public function test_get_connection_stats(): void
    {
        $this->seedTestData();

        $stats = $this->service->getConnectionStats();
        
        $this->assertArrayHasKey('connection_name', $stats);
        $this->assertArrayHasKey('is_connected', $stats);
        $this->assertArrayHasKey('faculty_count', $stats);
        $this->assertArrayHasKey('student_count', $stats);
        $this->assertArrayHasKey('assignment_count', $stats);
        
        $this->assertEquals('school_db', $stats['connection_name']);
        $this->assertTrue($stats['is_connected']);
        $this->assertEquals(1, $stats['faculty_count']); // Only active faculty
        $this->assertEquals(2, $stats['student_count']);
        $this->assertEquals(3, $stats['assignment_count']);
    }

    public function test_execute_read_only_query(): void
    {
        $this->seedTestData();

        $results = $this->service->executeReadOnlyQuery(
            'SELECT * FROM faculty WHERE status = ?',
            ['active']
        );
        
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->first_name);
    }

    public function test_execute_read_only_query_blocks_dangerous_operations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query contains dangerous keyword: INSERT');

        $this->service->executeReadOnlyQuery('INSERT INTO faculty (first_name) VALUES (?)', ['Test']);
    }

    public function test_execute_read_only_query_blocks_update_operations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query contains dangerous keyword: UPDATE');

        $this->service->executeReadOnlyQuery('UPDATE faculty SET first_name = ?', ['Test']);
    }

    public function test_execute_read_only_query_blocks_delete_operations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query contains dangerous keyword: DELETE');

        $this->service->executeReadOnlyQuery('DELETE FROM faculty WHERE faculty_id = ?', [1]);
    }

    public function test_handles_database_errors_gracefully(): void
    {
        // Test with invalid faculty ID - this should return empty collection without error
        $assignments = $this->service->getCurrentFacultyAssignments(999);
        
        $this->assertTrue($assignments->isEmpty());
    }
}