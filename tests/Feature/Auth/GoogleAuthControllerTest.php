<?php

namespace Tests\Feature\Auth;

use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear any existing sessions
        Session::flush();
    }

    public function test_show_login_page()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('Faculty Grading System');
        $response->assertSee('Sign in with Google');
    }

    public function test_redirect_to_google()
    {
        $response = $this->get('/auth/google');
        
        // Should redirect to Google OAuth
        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_google_callback_with_valid_lorma_email()
    {
        // Mock Socialite user with valid @lorma.edu email
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('123456789');
        $socialiteUser->shouldReceive('getEmail')->andReturn('john.doe@lorma.edu');
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
        $socialiteUser->token = 'mock_access_token';
        $socialiteUser->refreshToken = 'mock_refresh_token';
        $socialiteUser->expiresIn = 3600;

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $response = $this->get('/auth/google/callback');

        // Should redirect to dashboard
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');
        
        // Should create faculty record
        $this->assertDatabaseHas('faculties', [
            'google_id' => '123456789',
            'email' => 'john.doe@lorma.edu',
            'name' => 'John Doe'
        ]);

        // Should set session
        $this->assertNotNull(session('faculty_id'));
    }

    public function test_google_callback_with_invalid_domain()
    {
        // Mock Socialite user with invalid email domain
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('123456789');
        $socialiteUser->shouldReceive('getEmail')->andReturn('john.doe@gmail.com');
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $response = $this->get('/auth/google/callback');

        // Should redirect to login with error
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        
        // Should not create faculty record
        $this->assertDatabaseMissing('faculties', [
            'email' => 'john.doe@gmail.com'
        ]);

        // Should not set session
        $this->assertNull(session('faculty_id'));
    }

    public function test_google_callback_updates_existing_faculty()
    {
        // Create existing faculty
        $existingFaculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'john.doe@lorma.edu',
            'name' => 'John Doe Old',
            'access_token' => 'old_token',
            'refresh_token' => 'old_refresh_token',
            'token_expires_at' => now()->subHour()
        ]);

        // Mock Socialite user with updated info
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('123456789');
        $socialiteUser->shouldReceive('getEmail')->andReturn('john.doe@lorma.edu');
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe Updated');
        $socialiteUser->token = 'new_access_token';
        $socialiteUser->refreshToken = 'new_refresh_token';
        $socialiteUser->expiresIn = 3600;

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $response = $this->get('/auth/google/callback');

        // Should redirect to dashboard
        $response->assertRedirect('/dashboard');
        
        // Should update existing faculty record
        $existingFaculty->refresh();
        $this->assertEquals('John Doe Updated', $existingFaculty->name);
        $this->assertEquals('new_access_token', $existingFaculty->access_token);
        $this->assertEquals('new_refresh_token', $existingFaculty->refresh_token);
        $this->assertTrue($existingFaculty->token_expires_at->isFuture());
    }

    public function test_logout()
    {
        // Create and authenticate faculty
        $faculty = Faculty::create([
            'google_id' => '123456789',
            'email' => 'john.doe@lorma.edu',
            'name' => 'John Doe',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'token_expires_at' => now()->addHour()
        ]);

        session(['faculty_id' => $faculty->id]);

        $response = $this->post('/logout');

        // Should redirect to login
        $response->assertRedirect('/login');
        $response->assertSessionHas('success');
        
        // Should clear session
        $this->assertNull(session('faculty_id'));
    }

    public function test_faculty_model_has_valid_domain_method()
    {
        $validFaculty = new Faculty(['email' => 'test@lorma.edu']);
        $invalidFaculty = new Faculty(['email' => 'test@gmail.com']);

        $this->assertTrue($validFaculty->hasValidDomain());
        $this->assertFalse($invalidFaculty->hasValidDomain());
    }

    public function test_faculty_model_has_valid_token_method()
    {
        $validTokenFaculty = new Faculty([
            'access_token' => 'valid_token',
            'token_expires_at' => now()->addHour()
        ]);

        $expiredTokenFaculty = new Faculty([
            'access_token' => 'expired_token',
            'token_expires_at' => now()->subHour()
        ]);

        $noTokenFaculty = new Faculty([
            'access_token' => null,
            'token_expires_at' => null
        ]);

        $this->assertTrue($validTokenFaculty->hasValidToken());
        $this->assertFalse($expiredTokenFaculty->hasValidToken());
        $this->assertFalse($noTokenFaculty->hasValidToken());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}