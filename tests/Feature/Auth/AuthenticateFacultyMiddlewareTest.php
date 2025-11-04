<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\AuthenticateFaculty;
use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AuthenticateFacultyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test route that uses the middleware
        Route::get('/test-protected', function (Request $request) {
            $faculty = $request->attributes->get('faculty');
            return response()->json([
                'authenticated' => true,
                'faculty_id' => $faculty ? $faculty->id : null,
                'faculty_email' => $faculty ? $faculty->email : null
            ]);
        })->middleware('auth.faculty');
        
        Session::flush();
    }

    public function test_middleware_redirects_unauthenticated_user()
    {
        $response = $this->get('/test-protected');
        
        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Please log in to access this page.');
    }

    public function test_middleware_redirects_when_faculty_not_found()
    {
        // Set invalid faculty ID in session
        session(['faculty_id' => 999]);
        
        $response = $this->get('/test-protected');
        
        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Invalid session. Please log in again.');
        $this->assertNull(session('faculty_id'));
    }

    public function test_middleware_redirects_invalid_domain()
    {
        // Create faculty with invalid domain
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'test@gmail.com',
            'name' => 'Test User',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->addHour()
        ]);

        session(['faculty_id' => $faculty->id]);
        
        $response = $this->get('/test-protected');
        
        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Access restricted to @lorma.edu accounts only.');
        $this->assertNull(session('faculty_id'));
    }

    public function test_middleware_allows_valid_faculty()
    {
        // Create valid faculty
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'test@lorma.edu',
            'name' => 'Test User',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->addHour()
        ]);

        session(['faculty_id' => $faculty->id]);
        
        $response = $this->get('/test-protected');
        
        $response->assertStatus(200);
        $response->assertJson([
            'authenticated' => true,
            'faculty_id' => $faculty->id,
            'faculty_email' => 'test@lorma.edu'
        ]);
    }

    public function test_middleware_allows_faculty_with_expired_token()
    {
        // Create faculty with expired token (should still allow access but log warning)
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'test@lorma.edu',
            'name' => 'Test User',
            'access_token' => 'expired_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->subHour()
        ]);

        session(['faculty_id' => $faculty->id]);
        
        $response = $this->get('/test-protected');
        
        // Should still allow access (token expiry doesn't block access, just API calls)
        $response->assertStatus(200);
        $response->assertJson([
            'authenticated' => true,
            'faculty_id' => $faculty->id,
            'faculty_email' => 'test@lorma.edu'
        ]);
    }

    public function test_middleware_makes_faculty_available_to_request()
    {
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'test@lorma.edu',
            'name' => 'Test User',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->addHour()
        ]);

        session(['faculty_id' => $faculty->id]);
        
        $response = $this->get('/test-protected');
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertEquals($faculty->id, $responseData['faculty_id']);
        $this->assertEquals($faculty->email, $responseData['faculty_email']);
    }

    public function test_dashboard_requires_authentication()
    {
        $response = $this->get('/');
        
        $response->assertRedirect('/login');
    }

    public function test_dashboard_works_with_authenticated_faculty()
    {
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'test@lorma.edu',
            'name' => 'Test User',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->addHour()
        ]);

        session(['faculty_id' => $faculty->id]);
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSee('Test User');
        $response->assertSee('test@lorma.edu');
    }
}