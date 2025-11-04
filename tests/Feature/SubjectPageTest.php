<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Faculty;

class SubjectPageTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_subjects_page_loads_for_authenticated_faculty(): void
    {
        $faculty = Faculty::factory()->create([
            'email' => 'test@lorma.edu'
        ]);

        $this->actingAs($faculty, 'faculty');

        $response = $this->get('/subjects');

        $response->assertStatus(200)
            ->assertViewIs('subjects.index');
    }

    public function test_classroom_page_loads_for_authenticated_faculty(): void
    {
        $faculty = Faculty::factory()->create([
            'email' => 'test@lorma.edu'
        ]);

        $this->actingAs($faculty, 'faculty');

        $response = $this->get('/classroom');

        $response->assertStatus(200)
            ->assertViewIs('classroom.index');
    }
}
