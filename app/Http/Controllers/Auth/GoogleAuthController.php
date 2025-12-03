<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Show the login page
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')
                ->scopes([
                    'openid',
                    'profile',
                    'email',
                    'https://www.googleapis.com/auth/classroom.courses.readonly',
                    'https://www.googleapis.com/auth/classroom.rosters.readonly',
                    'https://www.googleapis.com/auth/classroom.coursework.students.readonly'
                ])
                ->with(['access_type' => 'offline', 'prompt' => 'consent'])
                ->redirect();
        } catch (Exception $e) {
            Log::error('Google OAuth redirect failed: ' . $e->getMessage());
            return redirect()->route('auth.login')
                ->with('error', 'Unable to connect to Google. Please try again.');
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Check if there's an error from Google
            if ($request->has('error')) {
                Log::warning('Google OAuth error: ' . $request->get('error'));
                return redirect()->route('auth.login')
                    ->with('error', 'Authentication cancelled or failed. Please try again.');
            }

            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Validate @lorma.edu domain
            if (!$this->isValidDomain($googleUser->getEmail())) {
                Log::warning('Invalid domain login attempt: ' . $googleUser->getEmail());
                return redirect()->route('auth.login')
                    ->with('error', 'Access denied. Only @lorma.edu accounts are allowed.');
            }

            // Create or update faculty record
            $faculty = $this->createOrUpdateFaculty($googleUser);
            
            // Log in the faculty using Laravel's auth system
            auth('faculty')->login($faculty);
            
            // Also store faculty ID in session for backward compatibility
            session(['faculty_id' => $faculty->id]);
            
            Log::info('Faculty logged in successfully: ' . $faculty->email);
            
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome, ' . $faculty->name . '!');
                
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('Invalid state exception (possible session issue): ' . $e->getMessage());
            return redirect()->route('auth.login')
                ->with('error', 'Session expired. Please try logging in again.');
        } catch (Exception $e) {
            Log::error('Google OAuth callback failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('auth.login')
                ->with('error', 'Authentication failed. Please try again.');
        }
    }

    /**
     * Logout the faculty member
     */
    public function logout(Request $request)
    {
        $facultyId = session('faculty_id');
        if ($facultyId) {
            $faculty = Faculty::find($facultyId);
            if ($faculty) {
                Log::info('Faculty logged out: ' . $faculty->email);
            }
        }
        
        $request->session()->flush();
        return redirect()->route('auth.login')
            ->with('success', 'Successfully logged out');
    }

    /**
     * Validate if email domain is @lorma.edu
     */
    private function isValidDomain(string $email): bool
    {
        return str_ends_with(strtolower($email), '@lorma.edu');
    }

    /**
     * Create or update faculty record
     */
    private function createOrUpdateFaculty($googleUser): Faculty
    {
        $faculty = Faculty::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $facultyData = [
            'google_id' => $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName(),
            'access_token' => $googleUser->token,
            'refresh_token' => $googleUser->refreshToken,
            'token_expires_at' => $googleUser->expiresIn ? 
                now()->addSeconds($googleUser->expiresIn) : 
                now()->addHour()
        ];

        if ($faculty) {
            $faculty->update($facultyData);
        } else {
            $faculty = Faculty::create($facultyData);
        }

        return $faculty;
    }
}
