<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Activity;

class DashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_dashboard_loads_for_authenticated_faculty(): void
    {
        $faculty = Faculty::factory()->create([
            'email' => 'test@lorma.edu'
        ]);

        $this->actingAs($faculty, 'faculty');

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertViewIs('dashboard')
            ->assertViewHas(['faculty', 'stats', 'recentSubjects', 'recentActivities', 'subjectsNeedingAttention']);
    }

    public function test_dashboard_shows_correct_stats(): void
    {
        $faculty = Faculty::factory()->create([
            'email' => 'test@lorma.edu'
        ]);

        // Create test data
        $subject1 = Subject::factory()->create([
            'faculty_id' => $faculty->id,
            'gcr_class_id' => 'gcr_123'
        ]);
        
        $subject2 = Subject::factory()->create([
            'faculty_id' => $faculty->id,
            'gcr_class_id' => null
        ]);

        Activity::factory()->create([
            'subject_id' => $subject1->id,
            'term' => 'prelim'
        ]);

        Activity::factory()->create([
            'subject_id' => $subject1->id,
            'term' => 'midterm'
        ]);

        $this->actingAs($faculty, 'faculty');

        $response = $this->get('/');

        $response->assertStatus(200);
        
        $stats = $response->viewData('stats');
        
        $this->assertEquals(2, $stats['total_subjects']);
        $this->assertEquals(2, $stats['total_activities']);
        $this->assertEquals(1, $stats['connected_subjects']);
        $this->assertEquals(50.0, $stats['gcr_connection_rate']);
    }

    public function test_dashboard_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_shows_subjects_needing_attention(): void
    {
        $faculty = Faculty::factory()->create([
            'email' => 'test@lorma.edu'
        ]);

        // Create a subject with no activities and no GCR connection
        $subject = Subject::factory()->create([
            'faculty_id' => $faculty->id,
            'gcr_class_id' => null
        ]);

        $this->actingAs($faculty, 'faculty');

        $response = $this->get('/');

        $response->assertStatus(200);
        
        $subjectsNeedingAttention = $response->viewData('subjectsNeedingAttention');
        
        $this->assertCount(1, $subjectsNeedingAttention);
        $this->assertEquals($subject->id, $subjectsNeedingAttention[0]['subject']->id);
        $this->assertContains('No activities created', $subjectsNeedingAttention[0]['issues']);
        $this->assertContains('Not connected to Google Classroom', $subjectsNeedingAttention[0]['issues']);
    }
}
