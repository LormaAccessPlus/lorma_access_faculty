<?php

namespace App\Http\Middleware;

use App\Models\Faculty;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AuthenticateFaculty
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if faculty is authenticated via Laravel's auth system
        if (auth('faculty')->check()) {
            $faculty = auth('faculty')->user();
            
            // Validate @lorma.edu domain
            if (!$faculty->hasValidDomain()) {
                auth('faculty')->logout();
                Log::warning('Invalid domain access attempt: ' . $faculty->email);
                return redirect()->route('auth.login')
                    ->with('error', 'Access restricted to @lorma.edu accounts only.');
            }

            // Check if token is still valid (optional - for API calls)
            if (!$faculty->hasValidToken()) {
                Log::info('Faculty token expired for: ' . $faculty->email);
                // Don't force logout, but token will need refresh for API calls
            }

            // Make faculty available to the request
            $request->attributes->set('faculty', $faculty);

            return $next($request);
        }

        // Fallback to session-based authentication for backward compatibility
        $facultyId = session('faculty_id');
        
        if (!$facultyId) {
            return redirect()->route('auth.login')
                ->with('error', 'Please log in to access this page.');
        }

        // Get faculty from database
        $faculty = Faculty::find($facultyId);
        
        if (!$faculty) {
            session()->flush();
            Log::warning('Faculty not found in database for session ID: ' . $facultyId);
            return redirect()->route('auth.login')
                ->with('error', 'Invalid session. Please log in again.');
        }

        // Validate @lorma.edu domain
        if (!$faculty->hasValidDomain()) {
            session()->flush();
            Log::warning('Invalid domain access attempt: ' . $faculty->email);
            return redirect()->route('auth.login')
                ->with('error', 'Access restricted to @lorma.edu accounts only.');
        }

        // Check if token is still valid (optional - for API calls)
        if (!$faculty->hasValidToken()) {
            Log::info('Faculty token expired for: ' . $faculty->email);
            // Don't force logout, but token will need refresh for API calls
        }

        // Make faculty available to the request
        $request->attributes->set('faculty', $faculty);

        return $next($request);
    }
}
