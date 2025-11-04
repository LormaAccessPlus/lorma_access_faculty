<?php

namespace Tests\Feature;

use App\Models\SchoolFaculty;
use App\Models\SchoolStudent;
use App\Models\SchoolSubjectAssignment;
use App\Services\SchoolDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolDatabaseIntegrationTest extends TestCase
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
        $this->seedTestData();
        
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
                'middle_name' => 'A.',
                'last_name' => 'Doe',
                'email' => 'john.doe@lorma.edu',
                'department_id' => 1,
                'status' => 'active'
            ],
            [
                'faculty_id' => 2,
                'faculty_code' => 'FAC002',
                'first_name' => 'Jane',
                'middle_name' => 'B.',
                'last_name' => 'Smith',
                'email' => 'jane.smith@lorma.edu',
                'department_id' => 1,
                'status' => 'active'
            ]
        ]);

        // Insert students
        $connection->table('students')->insert([
            [
                'student_id' => 1,
                'student_number' => '2024001',
                'first_name' => 'Alice',
                'middle_name' => 'C.',
                'last_name' => 'Johnson',
                'email' => 'alice.johnson@student.lorma.edu',
                'course_id' => 1,
                'year_level' => 1,
                'section' => 'A',
                'status' => 'active'
            ],
            [
                'student_id' => 2,
                'student_number' => '2024002',
                'first_name' => 'Bob',
                'middle_name' => 'D.',
                'last_name' => 'Wilson',
                'email' => 'bob.wilson@student.lorma.edu',
                'course_id' => 1,
                'year_level' => 1,
                'section' => 'A',
                'status' => 'active'
            ],
            [
                'student_id' => 3,
                'student_number' => '2024003',
                'first_name' => 'Charlie',
                'middle_name' => '',
                'last_name' => 'Brown',
                'email' => 'charlie.brown@student.lorma.edu',
                'course_id' => 2,
                'year_level' => 2,
                'section' => 'B',
                'status' => 'active'
            ]
        ]);

        // Insert subject assignments for current semester
        $connection->table('subject_assignments')->insert([
            [
                'assignment_id' => 1,
                'faculty_id' => 1,
                'subject_id' => 101,
                'subject_code' => 'CS101',
                'subject_name' => 'Introduction to Computer Science',
                'section' => 'A',
                'units' => 3,
                'schedule' => 'MWF 8:00-9:00 AM',
                'room' => 'Room 101',
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
                'schedule' => 'TTH 10:00-11:30 AM',
                'room' => 'Room 102',
                'academic_year' => 2024,
                'semester' => 1,
                'status' => 'active'
            ],
            [
                'assignment_id' => 3,
                'faculty_id' => 2,
                'subject_id' => 103,
                'subject_code' => 'MATH101',
                'subject_name' => 'College Algebra',
                'section' => 'A',
                'units' => 3,
                'schedule' => 'MWF 9:00-10:00 AM',
                'room' => 'Room 201',
                'academic_year' => 2024,
                'semester' => 1,
                'status' => 'active'
            ]
        ]);

        // Insert historical assignments
        $connection->table('subject_assignments')->insert([
            [
                'assignment_id' => 4,
                'faculty_id' => 1,
                'subject_id' => 104,
                'subject_code' => 'CS103',
                'subject_name' => 'Data Structures',
                'section' => 'A',
                'units' => 3,
                'schedule' => 'MWF 11:00-12:00 PM',
                'room' => 'Room 103',
                'academic_year' => 2023,
                'semester' => 2,
                'status' => 'active'
            ],
            [
                'assignment_id' => 5,
                'faculty_id' => 1,
                'subject_id' => 105,
                'subject_code' => 'CS104',
                'subject_name' => 'Algorithms',
                'section' => 'B',
                'units' => 3,
                'schedule' => 'TTH 1:00-2:30 PM',
                'room' => 'Room 104',
                'academic_year' => 2023,
                'semester' => 1,
                'status' => 'active'
            ]
        ]);

        // Insert enrollments
        $connection->table('enrollments')->insert([
            ['enrollment_id' => 1, 'student_id' => 1, 'assignment_id' => 1, 'enrollment_date' => '2024-01-15', 'status' => 'active'],
            ['enrollment_id' => 2, 'student_id' => 2, 'assignment_id' => 1, 'enrollment_date' => '2024-01-15', 'status' => 'active'],
            ['enrollment_id' => 3, 'student_id' => 1, 'assignment_id' => 2, 'enrollment_date' => '2024-01-15', 'status' => 'active'],
            ['enrollment_id' => 4, 'student_id' => 3, 'assignment_id' => 3, 'enrollment_date' => '2024-01-15', 'status' => 'active'],
        ]);
    }

    public function test_complete_faculty_workflow(): void
    {
        // Set current academic period
        config(['app.current_academic_year' => 2024]);
        config(['app.current_semester' => 1]);

        // 1. Find faculty by email
        $faculty = $this->service->findFacultyByEmail('john.doe@lorma.edu');
        $this->assertNotNull($faculty);
        $this->assertEquals('John A. Doe', $faculty->full_name);

        // 2. Get current assignments
        $currentAssignments = $this->service->getCurrentFacultyAssignments($faculty->faculty_id);
        $this->assertCount(2, $currentAssignments);
        
        $assignment1 = $currentAssignments->where('subject_code', 'CS101')->first();
        $assignment2 = $currentAssignments->where('subject_code', 'CS102')->first();
        
        $this->assertNotNull($assignment1);
        $this->assertNotNull($assignment2);
        $this->assertEquals('Introduction to Computer Science', $assignment1->subject_name);
        $this->assertEquals('Programming Fundamentals', $assignment2->subject_name);

        // 3. Get assignment history
        $history = $this->service->getFacultyAssignmentHistory($faculty->faculty_id);
        $this->assertCount(4, $history); // 2 current + 2 historical

        // 4. Get specific period assignments
        $pastAssignments = $this->service->getFacultyAssignmentsByPeriod($faculty->faculty_id, 2023, 2);
        $this->assertCount(1, $pastAssignments);
        $this->assertEquals('CS103', $pastAssignments->first()->subject_code);
    }

    public function test_complete_student_workflow(): void
    {
        // 1. Find student by email
        $student = $this->service->findStudentByEmail('alice.johnson@student.lorma.edu');
        $this->assertNotNull($student);
        $this->assertEquals('Alice C. Johnson', $student->full_name);

        // 2. Find student by number
        $studentByNumber = $this->service->findStudentByNumber('2024001');
        $this->assertNotNull($studentByNumber);
        $this->assertEquals($student->student_id, $studentByNumber->student_id);

        // 3. Search students
        $searchResults = $this->service->searchStudents('Alice');
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Alice', $searchResults->first()->first_name);

        // 4. Search by partial email
        $emailSearchResults = $this->service->searchStudents('alice.johnson');
        $this->assertCount(1, $emailSearchResults);
        $this->assertEquals('Alice', $emailSearchResults->first()->first_name);
    }

    public function test_assignment_and_enrollment_workflow(): void
    {
        // 1. Get specific assignment
        $assignment = $this->service->getSubjectAssignment(1);
        $this->assertNotNull($assignment);
        $this->assertEquals('CS101 - Introduction to Computer Science (A)', $assignment->display_name);
        $this->assertNotNull($assignment->faculty);
        $this->assertEquals('John', $assignment->faculty->first_name);

        // 2. Get students in assignment
        $students = $this->service->getStudentsInAssignment(1);
        $this->assertCount(2, $students);
        
        $studentNames = $students->pluck('first_name')->toArray();
        $this->assertContains('Alice', $studentNames);
        $this->assertContains('Bob', $studentNames);
    }

    public function test_academic_period_queries(): void
    {
        // 1. Get available academic years
        $years = $this->service->getAvailableAcademicYears();
        $this->assertCount(2, $years);
        $this->assertTrue($years->contains(2024));
        $this->assertTrue($years->contains(2023));
        $this->assertEquals(2024, $years->first()); // Should be ordered desc

        // 2. Get available semesters for 2024
        $semesters2024 = $this->service->getAvailableSemesters(2024);
        $this->assertCount(1, $semesters2024);
        $this->assertTrue($semesters2024->contains(1));

        // 3. Get available semesters for 2023
        $semesters2023 = $this->service->getAvailableSemesters(2023);
        $this->assertCount(2, $semesters2023);
        $this->assertTrue($semesters2023->contains(1));
        $this->assertTrue($semesters2023->contains(2));
    }

    public function test_model_relationships_work_correctly(): void
    {
        // Test faculty -> subject assignments relationship
        $faculty = SchoolFaculty::find(1);
        $assignments = $faculty->subjectAssignments;
        $this->assertCount(4, $assignments); // 2 current + 2 historical

        // Test current assignments relationship
        config(['app.current_academic_year' => 2024]);
        config(['app.current_semester' => 1]);
        
        $currentAssignments = $faculty->currentSubjectAssignments;
        $this->assertCount(2, $currentAssignments);

        // Test assignment -> faculty relationship
        $assignment = SchoolSubjectAssignment::find(1);
        $this->assertNotNull($assignment->faculty);
        $this->assertEquals('John', $assignment->faculty->first_name);

        // Test assignment -> students relationship
        $students = $assignment->students;
        $this->assertCount(2, $students);
    }

    public function test_scopes_work_correctly(): void
    {
        // Test faculty scopes
        $activeFaculty = SchoolFaculty::active()->get();
        $this->assertCount(2, $activeFaculty);

        $facultyByEmail = SchoolFaculty::byEmail('john.doe@lorma.edu')->first();
        $this->assertNotNull($facultyByEmail);
        $this->assertEquals('John', $facultyByEmail->first_name);

        // Test student scopes
        $activeStudents = SchoolStudent::active()->get();
        $this->assertCount(3, $activeStudents);

        $studentsByYear = SchoolStudent::byYearLevel(1)->get();
        $this->assertCount(2, $studentsByYear);

        $studentsByCourse = SchoolStudent::byCourse(1)->get();
        $this->assertCount(2, $studentsByCourse);

        // Test assignment scopes
        config(['app.current_academic_year' => 2024]);
        config(['app.current_semester' => 1]);
        
        $currentAssignments = SchoolSubjectAssignment::current()->get();
        $this->assertCount(3, $currentAssignments);

        $facultyAssignments = SchoolSubjectAssignment::byFaculty(1)->get();
        $this->assertCount(4, $facultyAssignments);

        $yearAssignments = SchoolSubjectAssignment::byAcademicYear(2024)->get();
        $this->assertCount(3, $yearAssignments);
    }

    public function test_connection_statistics(): void
    {
        $stats = $this->service->getConnectionStats();
        
        $this->assertArrayHasKey('connection_name', $stats);
        $this->assertArrayHasKey('is_connected', $stats);
        $this->assertArrayHasKey('faculty_count', $stats);
        $this->assertArrayHasKey('student_count', $stats);
        $this->assertArrayHasKey('assignment_count', $stats);
        
        $this->assertEquals('school_db', $stats['connection_name']);
        $this->assertTrue($stats['is_connected']);
        $this->assertEquals(2, $stats['faculty_count']);
        $this->assertEquals(3, $stats['student_count']);
        $this->assertEquals(5, $stats['assignment_count']);
    }

    public function test_read_only_query_execution(): void
    {
        // Test successful read-only query
        $results = $this->service->executeReadOnlyQuery(
            'SELECT f.first_name, f.last_name, COUNT(sa.assignment_id) as assignment_count 
             FROM faculty f 
             LEFT JOIN subject_assignments sa ON f.faculty_id = sa.faculty_id 
             WHERE f.status = ? 
             GROUP BY f.faculty_id',
            ['active']
        );
        
        $this->assertCount(2, $results);
        
        $johnRecord = $results->where('first_name', 'John')->first();
        $this->assertNotNull($johnRecord);
        $this->assertEquals(4, $johnRecord->assignment_count);
        
        $janeRecord = $results->where('first_name', 'Jane')->first();
        $this->assertNotNull($janeRecord);
        $this->assertEquals(1, $janeRecord->assignment_count);
    }
}