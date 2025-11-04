<?php

namespace Tests\Feature;

use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated users are redirected to login
     */
    public function test_unauthenticated_users_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('auth.login'));
    }

    /**
     * Test that login page is accessible
     */
    public function test_login_page_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Faculty Grading System');
        $response->assertSee('Sign in with Google');
    }

    /**
     * Test that authenticated faculty can access dashboard
     */
    public function test_authenticated_faculty_can_access_dashboard(): void
    {
        // Create faculty with valid @lorma.edu email
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'test@lorma.edu',
            'name' => 'Test Faculty',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->addHour()
        ]);

        // Set faculty session
        session(['faculty_id' => $faculty->id]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Welcome to the Faculty Grading System');
    }

    /**
     * Test that non-lorma.edu emails are rejected
     */
    public function test_non_lorma_emails_rejected(): void
    {
        // Create faculty with invalid email domain
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'test@gmail.com',
            'name' => 'Test User',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->addHour()
        ]);

        // Set faculty session
        session(['faculty_id' => $faculty->id]);

        $response = $this->get('/');
        $response->assertRedirect(route('auth.login'));
        $response->assertSessionHas('error', 'Access restricted to @lorma.edu accounts only.');
    }
}
