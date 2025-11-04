<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\StudentMappingService;
use App\Models\Subject;
use App\Models\StudentMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

class StudentMappingServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentMappingService $service;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StudentMappingService();
        $this->subject = Subject::factory()->create();
    }

    public function test_auto_match_students_with_exact_email_match()
    {
        $gcrStudents = [
            [
                'userId' => 'gcr_123',
                'profile' => [
                    'name' => ['fullName' => 'John Doe']
                ],
                'emailAddress' => 'john.doe@student.lorma.edu'
            ]
        ];

        $schoolStudents = collect([
            (object) [
                'id' => 1,
                'full_name' => 'John Doe',
                'email' => 'john.doe@student.lorma.edu'
            ]
        ]);

        // Mock the getSchoolStudentsForSubject method
        $service = $this->getMockBuilder(StudentMappingService::class)
            ->onlyMethods(['getSchoolStudentsForSubject'])
            ->getMock();

        $service->expects($this->once())
            ->method('getSchoolStudentsForSubject')
            ->with($this->subject)
            ->willReturn($schoolStudents);

        $results = $service->autoMatchStudents($this->subject, $gcrStudents);

        $this->assertCount(1, $results['matches']);
        $this->assertCount(0, $results['conflicts']);
        $this->assertCount(0, $results['unmatched']);

        $match = $results['matches'][0];
        $this->assertEquals('gcr_123', $match['gcr_student']['userId']);
        $this->assertEquals(1, $match['school_student']->id);
        $this->assertGreaterThanOrEqual(0.8, $match['confidence']);
        $this->assertEquals('email_exact', $match['match_type']);
    }

    public function test_auto_match_students_with_name_similarity()
    {
        $gcrStudents = [
            [
                'userId' => 'gcr_456',
                'profile' => [
                    'name' => ['fullName' => 'Jane Marie Smith']
                ],
                'emailAddress' => 'jane.smith@gmail.com'
            ]
        ];

        $schoolStudents = collect([
            (object) [
                'id' => 2,
                'full_name' => 'Jane M. Smith',
                'email' => 'jane.smith@student.lorma.edu'
            ]
        ]);

        $service = $this->getMockBuilder(StudentMappingService::class)
            ->onlyMethods(['getSchoolStudentsForSubject'])
            ->getMock();

        $service->expects($this->once())
            ->method('getSchoolStudentsForSubject')
            ->with($this->subject)
            ->willReturn($schoolStudents);

        $results = $service->autoMatchStudents($this->subject, $gcrStudents);

        // This should be a conflict or match depending on confidence threshold
        $this->assertTrue(
            count($results['matches']) > 0 || count($results['conflicts']) > 0
        );
    }

    public function test_auto_match_students_with_no_matches()
    {
        $gcrStudents = [
            [
                'userId' => 'gcr_789',
                'profile' => [
                    'name' => ['fullName' => 'Unknown Student']
                ],
                'emailAddress' => 'unknown@example.com'
            ]
        ];

        $schoolStudents = collect([
            (object) [
                'id' => 3,
                'full_name' => 'Completely Different Name',
                'email' => 'different@student.lorma.edu'
            ]
        ]);

        $service = $this->getMockBuilder(StudentMappingService::class)
            ->onlyMethods(['getSchoolStudentsForSubject'])
            ->getMock();

        $service->expects($this->once())
            ->method('getSchoolStudentsForSubject')
            ->with($this->subject)
            ->willReturn($schoolStudents);

        $results = $service->autoMatchStudents($this->subject, $gcrStudents);

        $this->assertCount(0, $results['matches']);
        $this->assertCount(1, $results['unmatched']);
    }

    public function test_name_similarity_calculation()
    {
        $service = new class extends StudentMappingService {
            public function testNameSimilarity($name1, $name2) {
                return $this->nameSimilarity($name1, $name2);
            }
        };

        // Exact match
        $this->assertEquals(1.0, $service->testNameSimilarity('John Doe', 'John Doe'));

        // Similar names
        $similarity = $service->testNameSimilarity('John Doe', 'John D. Doe');
        $this->assertGreaterThan(0.7, $similarity);

        // Different names
        $similarity = $service->testNameSimilarity('John Doe', 'Jane Smith');
        $this->assertLessThan(0.5, $similarity);
    }

    public function test_email_similarity_calculation()
    {
        $service = new class extends StudentMappingService {
            public function testEmailSimilarity($email1, $email2) {
                return $this->emailSimilarity($email1, $email2);
            }
        };

        // Same username, different domain
        $similarity = $service->testEmailSimilarity('john.doe@gmail.com', 'john.doe@student.lorma.edu');
        $this->assertEquals(1.0, $similarity);

        // Similar usernames
        $similarity = $service->testEmailSimilarity('john.doe@gmail.com', 'johndoe@student.lorma.edu');
        $this->assertGreaterThan(0.7, $similarity);

        // Different usernames
        $similarity = $service->testEmailSimilarity('john.doe@gmail.com', 'jane.smith@student.lorma.edu');
        $this->assertLessThan(0.5, $similarity);
    }

    public function test_save_matches()
    {
        $matches = [
            [
                'gcr_student' => [
                    'userId' => 'gcr_123',
                    'profile' => ['name' => ['fullName' => 'John Doe']],
                    'emailAddress' => 'john.doe@gmail.com'
                ],
                'school_student' => (object) ['id' => 1],
                'confidence' => 0.95
            ]
        ];

        $this->service->saveMatches($this->subject, $matches);

        $this->assertDatabaseHas('student_mappings', [
            'subject_id' => $this->subject->id,
            'gcr_student_id' => 'gcr_123',
            'school_student_id' => 1,
            'student_name' => 'John Doe',
            'student_email' => 'john.doe@gmail.com',
            'mapping_confidence' => 0.95
        ]);
    }

    public function test_create_manual_mapping()
    {
        $gcrStudent = [
            'userId' => 'gcr_manual',
            'profile' => ['name' => ['fullName' => 'Manual Student']],
            'emailAddress' => 'manual@gmail.com'
        ];

        $mapping = $this->service->createManualMapping($this->subject, $gcrStudent, 5);

        $this->assertInstanceOf(StudentMapping::class, $mapping);
        $this->assertEquals($this->subject->id, $mapping->subject_id);
        $this->assertEquals('gcr_manual', $mapping->gcr_student_id);
        $this->assertEquals(5, $mapping->school_student_id);
        $this->assertEquals('Manual Student', $mapping->student_name);
        $this->assertEquals('manual@gmail.com', $mapping->student_email);
        $this->assertEquals(1.0, $mapping->mapping_confidence); // Manual mappings have full confidence
    }

    public function test_get_mappings_for_subject()
    {
        // Create some test mappings
        StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'student_name' => 'Student A'
        ]);
        
        StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'student_name' => 'Student B'
        ]);

        // Create mapping for different subject
        $otherSubject = Subject::factory()->create();
        StudentMapping::factory()->create([
            'subject_id' => $otherSubject->id,
            'student_name' => 'Other Student'
        ]);

        $mappings = $this->service->getMappingsForSubject($this->subject);

        $this->assertCount(2, $mappings);
        $this->assertTrue($mappings->every(fn($mapping) => $mapping->subject_id === $this->subject->id));
    }

    public function test_delete_mapping()
    {
        $mapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $result = $this->service->deleteMapping($mapping->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('student_mappings', ['id' => $mapping->id]);
    }

    public function test_get_mapping_conflicts_low_confidence()
    {
        // Create a low confidence mapping
        StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'mapping_confidence' => 0.6
        ]);

        // Create a high confidence mapping
        StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'mapping_confidence' => 0.9
        ]);

        $conflicts = $this->service->getMappingConflicts($this->subject);

        $this->assertCount(1, $conflicts);
        $this->assertEquals('low_confidence', $conflicts[0]['reason']);
        $this->assertEquals(0.6, $conflicts[0]['confidence']);
    }

    public function test_get_mapping_conflicts_duplicate_school_student()
    {
        // Create duplicate mappings to same school student
        StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'school_student_id' => 1,
            'student_name' => 'Student A',
            'mapping_confidence' => 0.9 // High confidence to avoid low confidence conflict
        ]);

        StudentMapping::factory()->create([
            'subject_id' => $this->subject->id,
            'school_student_id' => 1,
            'student_name' => 'Student B',
            'mapping_confidence' => 0.9 // High confidence to avoid low confidence conflict
        ]);

        $conflicts = $this->service->getMappingConflicts($this->subject);

        $this->assertCount(1, $conflicts);
        $this->assertEquals('duplicate_school_student', $conflicts[0]['reason']);
        $this->assertEquals(1, $conflicts[0]['school_student_id']);
        $this->assertCount(2, $conflicts[0]['mappings']);
    }

    public function test_resolve_conflict()
    {
        $keepMapping = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $removeMapping1 = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $removeMapping2 = StudentMapping::factory()->create([
            'subject_id' => $this->subject->id
        ]);

        $result = $this->service->resolveConflict(
            $keepMapping->id,
            [$removeMapping1->id, $removeMapping2->id]
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('student_mappings', ['id' => $keepMapping->id]);
        $this->assertDatabaseMissing('student_mappings', ['id' => $removeMapping1->id]);
        $this->assertDatabaseMissing('student_mappings', ['id' => $removeMapping2->id]);
    }

    public function test_string_similarity()
    {
        $service = new class extends StudentMappingService {
            public function testStringSimilarity($str1, $str2) {
                return $this->stringSimilarity($str1, $str2);
            }
        };

        // Identical strings
        $this->assertEquals(1.0, $service->testStringSimilarity('test', 'test'));

        // Similar strings
        $similarity = $service->testStringSimilarity('test', 'tests');
        $this->assertGreaterThan(0.7, $similarity);

        // Different strings
        $similarity = $service->testStringSimilarity('test', 'completely different');
        $this->assertLessThan(0.3, $similarity);
    }

    public function test_normalize_name()
    {
        $service = new class extends StudentMappingService {
            public function testNormalizeName($name) {
                return $this->normalizeName($name);
            }
        };

        $this->assertEquals('john doe', $service->testNormalizeName('John Doe'));
        $this->assertEquals('john doe', $service->testNormalizeName('  John   Doe  '));
        $this->assertEquals('john doe', $service->testNormalizeName('John Doe Jr.'));
        $this->assertEquals('john doe', $service->testNormalizeName('John Doe II'));
    }
}