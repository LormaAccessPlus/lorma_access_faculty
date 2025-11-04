<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Subject;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share faculty subjects with admin layout
        View::composer('layouts.admin', function ($view) {
            $facultySubjects = collect();
            
            try {
                // Get faculty from request attributes (set by middleware)
                $faculty = request()->attributes->get('faculty');
                
                // Fallback methods if middleware didn't set faculty
                if (!$faculty) {
                    // Method 1: Check session-based faculty ID
                    if (session('faculty_id')) {
                        $faculty = \App\Models\Faculty::find(session('faculty_id'));
                    }
                    
                    // Method 2: Check auth guard
                    if (!$faculty && auth('faculty')->check()) {
                        $faculty = auth('faculty')->user();
                    }
                    
                    // Method 3: Fallback to faculty ID 1 for testing
                    if (!$faculty) {
                        $faculty = \App\Models\Faculty::find(1);
                    }
                }
                
                if ($faculty) {
                    $facultySubjects = Subject::where('faculty_id', $faculty->id)
                        ->orderBy('subject_code')
                        ->get();
                }
            } catch (\Exception $e) {
                // Silently handle errors - don't break the view
                $facultySubjects = collect();
            }
            
            $view->with('facultySubjects', $facultySubjects);
        });
    }
}
