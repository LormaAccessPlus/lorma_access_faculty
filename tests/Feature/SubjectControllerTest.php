<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SubjectControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Faculty $faculty;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faculty = Faculty::factory()->create();
    }

    public function test_faculty_can_view_subjects_index(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $response = $this->get(route('subjects.index'));

        $response->assertStatus(200);
        $response->assertViewIs('subjects.index');
        $response->assertViewHas(['currentSubjects', 'pastSubjects']);
    }

    public function test_faculty_can_create_subject(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $subjectData = [
            'subject_code' => 'CS101',
            'subject_name' => 'Introduction to Computer Science',
            'section' => 'A',
            'type' => 'lecture_only',
            'academic_year' => '2024-2025',
            'semester' => '1st Semester',
        ];

        $response = $this->post(route('subjects.store'), $subjectData);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', array_merge($subjectData, [
            'faculty_id' => $this->faculty->id
        ]));
    }

    public function test_faculty_can_view_own_subject(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id
        ]);

        $response = $this->get(route('subjects.show', $subject));

        $response->assertStatus(200);
        $response->assertViewIs('subjects.show');
        $response->assertViewHas('subject', $subject);
    }

    public function test_faculty_cannot_view_other_faculty_subject(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $otherFaculty = Faculty::factory()->create();
        $subject = Subject::factory()->create([
            'faculty_id' => $otherFaculty->id
        ]);

        $response = $this->get(route('subjects.show', $subject));

        $response->assertStatus(403);
    }

    public function test_faculty_can_update_own_subject(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id
        ]);

        $updateData = [
            'subject_code' => 'CS102',
            'subject_name' => 'Advanced Computer Science',
            'section' => 'B',
            'type' => 'lecture_lab',
            'academic_year' => '2024-2025',
            'semester' => '2nd Semester',
        ];

        $response = $this->put(route('subjects.update', $subject), $updateData);

        $response->assertRedirect(route('subjects.show', $subject));
        $this->assertDatabaseHas('subjects', array_merge($updateData, [
            'id' => $subject->id,
            'faculty_id' => $this->faculty->id
        ]));
    }

    public function test_faculty_cannot_update_other_faculty_subject(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $otherFaculty = Faculty::factory()->create();
        $subject = Subject::factory()->create([
            'faculty_id' => $otherFaculty->id
        ]);

        $updateData = [
            'subject_code' => 'CS102',
            'subject_name' => 'Advanced Computer Science',
            'section' => 'B',
            'type' => 'lecture_lab',
            'academic_year' => '2024-2025',
            'semester' => '2nd Semester',
        ];

        $response = $this->put(route('subjects.update', $subject), $updateData);

        $response->assertStatus(403);
    }

    public function test_faculty_can_delete_own_subject(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $subject = Subject::factory()->create([
            'faculty_id' => $this->faculty->id
        ]);

        $response = $this->delete(route('subjects.destroy', $subject));

        $response->assertRedirect(route('subjects.index'));
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_faculty_cannot_delete_other_faculty_subject(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $otherFaculty = Faculty::factory()->create();
        $subject = Subject::factory()->create([
            'faculty_id' => $otherFaculty->id
        ]);

        $response = $this->delete(route('subjects.destroy', $subject));

        $response->assertStatus(403);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_subject_creation_requires_valid_data(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $response = $this->post(route('subjects.store'), []);

        $response->assertSessionHasErrors([
            'subject_code',
            'subject_name',
            'section',
            'type',
            'academic_year',
            'semester'
        ]);
    }

    public function test_subject_type_must_be_valid(): void
    {
        $this->actingAs($this->faculty, 'faculty');

        $subjectData = [
            'subject_code' => 'CS101',
            'subject_name' => 'Introduction to Computer Science',
            'section' => 'A',
            'type' => 'invalid_type',
            'academic_year' => '2024-2025',
            'semester' => '1st Semester',
        ];

        $response = $this->post(route('subjects.store'), $subjectData);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_unauthenticated_user_cannot_access_subjects(): void
    {
        $response = $this->get(route('subjects.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
