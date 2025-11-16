<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ClassroomSyncController;
use App\Http\Controllers\GradeSyncController;

// Authentication Routes
Route::get('/login', [GoogleAuthController::class, 'showLogin'])->name('auth.login');
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('auth.logout');

// Development Login (REMOVE IN PRODUCTION!)
if (app()->environment('local')) {
    Route::get('/dev-login/{faculty_id?}', function ($facultyId = 1) {
        $faculty = \App\Models\Faculty::find($facultyId);
        
        if (!$faculty) {
            $faculties = \App\Models\Faculty::all(['id', 'name', 'email']);
            return response()->json([
                'error' => 'Faculty not found',
                'available_faculties' => $faculties
            ]);
        }
        
        // Log in the faculty
        auth('faculty')->login($faculty);
        session(['faculty_id' => $faculty->id]);
        
        return redirect('/')->with('success', "Logged in as {$faculty->name}");
    })->name('dev.login');
}

// Debug route (no auth required)
Route::get('/debug-mapping', function () {
    $mappingService = app(\App\Services\SubjectMappingService::class);
    
    // Test with faculty ID 1
    $facultyId = 1;
    $faculty = \App\Models\Faculty::find($facultyId);
    
    if (!$faculty) {
        return response()->json(['error' => 'Faculty not found']);
    }
    
    try {
        $gcrCourses = $mappingService->getAvailableGoogleClassroomCourses($facultyId);
        $schoolSubjects = $mappingService->getAvailableSchoolSubjects($faculty, '2024-2025', '1');
        $stats = $mappingService->getMappingStatistics($facultyId);
        
        return response()->json([
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'email' => $faculty->email,
                'school_faculty_id' => $faculty->school_faculty_id
            ],
            'gcr_courses' => $gcrCourses,
            'school_subjects' => $schoolSubjects,
            'stats' => $stats
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Debug mapping view (no auth required)
Route::get('/debug-mapping-view', function () {
    $mappingService = app(\App\Services\SubjectMappingService::class);
    
    // Test with faculty ID 1
    $facultyId = 1;
    $faculty = \App\Models\Faculty::find($facultyId);
    
    if (!$faculty) {
        return 'Faculty not found';
    }
    
    // Simulate the controller logic
    $currentAcademicYear = '2024-2025';
    $currentSemester = '1';
    
    $availableGcrCourses = $mappingService->getAvailableGoogleClassroomCourses($faculty->id);
    $availableSchoolSubjects = $mappingService->getAvailableSchoolSubjects($faculty, $currentAcademicYear, $currentSemester);
    
    $existingMappings = \App\Models\Subject::where('faculty_id', $faculty->id)
        ->where('type', 'mapped')
        ->with('studentMappings')
        ->orderBy('created_at', 'desc')
        ->get();
    
    $stats = $mappingService->getMappingStatistics($faculty->id);
    
    return view('subjects.mapping.index', compact(
        'availableGcrCourses',
        'availableSchoolSubjects',
        'existingMappings',
        'stats',
        'currentAcademicYear',
        'currentSemester'
    ));
});

// Debug mapping creation (no auth required)
Route::post('/debug-create-mapping', function (\Illuminate\Http\Request $request) {
    $mappingService = app(\App\Services\SubjectMappingService::class);
    
    // Test with faculty ID 1
    $facultyId = 1;
    $faculty = \App\Models\Faculty::find($facultyId);
    
    if (!$faculty) {
        return response()->json(['error' => 'Faculty not found']);
    }
    
    // Test mapping data
    $mappingData = [
        'faculty_id' => $faculty->id,
        'gcr_class_id' => '773975231968', // SOFTENG - 2025
        'school_subject_code' => 'CS101',
        'academic_year' => '2024-2025',
        'semester' => '1',
        'notes' => 'Test mapping'
    ];
    
    try {
        $result = $mappingService->createSubjectMapping($mappingData);
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Subject Mapping Routes (Temporary - no auth for testing)
Route::prefix('subjects/mapping')->name('subjects.mapping.')->group(function () {
    Route::get('/', [App\Http\Controllers\SubjectMappingController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\SubjectMappingController::class, 'store'])->name('store');
    Route::delete('/{subject}', [App\Http\Controllers\SubjectMappingController::class, 'destroy'])->name('destroy');
    Route::get('/gcr-courses', [App\Http\Controllers\SubjectMappingController::class, 'getGoogleClassroomCourses'])->name('gcr-courses');
    Route::post('/school-subjects', [App\Http\Controllers\SubjectMappingController::class, 'getSchoolSubjects'])->name('school-subjects');
    Route::get('/statistics', [App\Http\Controllers\SubjectMappingController::class, 'getStatistics'])->name('statistics');
});

// Protected Routes
Route::middleware(['auth.faculty'])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Test authentication route
    Route::get('/test-auth', function (Request $request) {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        return response()->json([
            'authenticated' => $faculty ? true : false,
            'faculty' => $faculty ? [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'email' => $faculty->email,
                'school_faculty_id' => $faculty->school_faculty_id
            ] : null,
            'session_faculty_id' => session('faculty_id'),
            'auth_guard_check' => auth('faculty')->check()
        ]);
    })->name('test-auth');
    
    // Subject Management Routes (Legacy - for existing mapped subjects)
    Route::resource('subjects', SubjectController::class)->except(['create', 'store']);
    Route::post('/subjects/sync', [SubjectController::class, 'sync'])->name('subjects.sync');
    
    // Google Classroom Integration Routes
    Route::prefix('classroom')->name('classroom.')->group(function () {
        Route::get('/', [ClassroomSyncController::class, 'index'])->name('index');
        Route::post('/fetch-courses', [ClassroomSyncController::class, 'fetchCourses'])->name('fetch-courses');
        Route::post('/connect-subject', [ClassroomSyncController::class, 'connectSubject'])->name('connect-subject');
        Route::post('/disconnect-subject', [ClassroomSyncController::class, 'disconnectSubject'])->name('disconnect-subject');
        Route::post('/sync-students', [ClassroomSyncController::class, 'syncStudents'])->name('sync-students');
        Route::post('/sync/{subject}/activities', [ClassroomSyncController::class, 'syncActivities'])->name('sync-activities');
        Route::get('/{subject}/available-coursework', [ClassroomSyncController::class, 'getAvailableCoursework'])->name('available-coursework');
        Route::post('/{subject}/import-selected', [ClassroomSyncController::class, 'importSelectedCoursework'])->name('import-selected');
        Route::post('/course-details', [ClassroomSyncController::class, 'getCourseDetails'])->name('course-details');
        Route::post('/test-connection', [ClassroomSyncController::class, 'testConnection'])->name('test-connection');
    });
    
    // Activity Management Routes
    Route::resource('activities', App\Http\Controllers\ActivityController::class);
    Route::post('/activities/bulk-create-gcr', [App\Http\Controllers\ActivityController::class, 'bulkCreateFromGCR'])->name('activities.bulk-create-gcr');
    
    // Test login route (for development only)
    Route::get('/test-login/{facultyId?}', function($facultyId = 2) {
        $faculty = \App\Models\Faculty::find($facultyId);
        if ($faculty) {
            auth('faculty')->login($faculty);
            return redirect('/dashboard')->with('success', "Logged in as {$faculty->name} for testing");
        }
        return redirect('/')->with('error', 'Faculty not found');
    })->name('test-login');
    
    // API Routes for Activity Management
    Route::prefix('api')->group(function () {
        Route::get('/subjects/{subject}/activities/organized', [App\Http\Controllers\ActivityController::class, 'getOrganized'])->name('api.activities.organized');
    });
    
    // Student Mapping Routes
    Route::prefix('subjects/{subject}/mappings')->name('mappings.')->group(function () {
        Route::get('/', [App\Http\Controllers\MappingController::class, 'index'])->name('index');
        Route::get('/auto-match', [App\Http\Controllers\MappingController::class, 'autoMatch'])->name('auto-match');
        Route::post('/save-auto-matches', [App\Http\Controllers\MappingController::class, 'saveAutoMatches'])->name('save-auto-matches');
        Route::post('/', [App\Http\Controllers\MappingController::class, 'store'])->name('store');
        Route::put('/{mapping}', [App\Http\Controllers\MappingController::class, 'update'])->name('update');
        Route::delete('/{mapping}', [App\Http\Controllers\MappingController::class, 'destroy'])->name('destroy');
        Route::get('/conflicts', [App\Http\Controllers\MappingController::class, 'conflicts'])->name('conflicts');
        Route::post('/resolve-conflict', [App\Http\Controllers\MappingController::class, 'resolveConflict'])->name('resolve-conflict');
        Route::get('/school-students', [App\Http\Controllers\MappingController::class, 'getSchoolStudents'])->name('school-students');
    });
    
    // Grade Matrix Routes
    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/subjects/{subject}/matrix', [App\Http\Controllers\GradeController::class, 'matrix'])->name('matrix');
        Route::get('/subjects/{subject}/term/{term?}', [App\Http\Controllers\GradeController::class, 'termGrades'])->name('term');
        Route::post('/update', [App\Http\Controllers\GradeController::class, 'updateGrade'])->name('update');
        Route::post('/update-exam-score', [App\Http\Controllers\GradeController::class, 'updateExamScore'])->name('update-exam-score');
        Route::get('/get', [App\Http\Controllers\GradeController::class, 'getGrade'])->name('get');
        Route::get('/get-term-grade', [App\Http\Controllers\GradeController::class, 'getTermGrade'])->name('get-term-grade');
        Route::post('/subjects/{subject}/import-from-classroom', [App\Http\Controllers\GradeController::class, 'importFromClassroom'])->name('import-from-classroom');
        
        // Export Routes
        Route::get('/subjects/{subject}/export/activities', [App\Http\Controllers\GradeController::class, 'exportActivities'])->name('export.activities');
        Route::get('/subjects/{subject}/export/pp', [App\Http\Controllers\GradeController::class, 'exportPP'])->name('export.pp');
        Route::get('/subjects/{subject}/export/term/{term}', [App\Http\Controllers\GradeController::class, 'exportTerm'])->name('export.term');
        
        // Grade Synchronization Routes
        Route::post('/sync/test-connections', [App\Http\Controllers\GradeSyncController::class, 'testConnections'])->name('sync.test-connections');
    });
    
    // Grade Synchronization Routes (Subject-specific)
    Route::prefix('subjects/{subject}/grades/sync')->name('grades.sync.')->group(function () {
        Route::get('/', [App\Http\Controllers\GradeSyncController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\GradeSyncController::class, 'sync'])->name('store');
        Route::post('/verify', [App\Http\Controllers\GradeSyncController::class, 'verify'])->name('verify');
        Route::post('/statistics', [App\Http\Controllers\GradeSyncController::class, 'statistics'])->name('statistics');
        Route::post('/preview', [App\Http\Controllers\GradeSyncController::class, 'preview'])->name('preview');
    });
});
