<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ClassroomSyncController;
use App\Http\Controllers\GradingSystemController;
use App\Http\Controllers\StudentMappingPageController;

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

// Subject Mapping Routes - REMOVED (duplicate of Google Classroom page)

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
        
        // Debug route - remove in production
        Route::get('/subjects/{subject}/activities/{activity}/debug', [App\Http\Controllers\DebugGradesController::class, 'debugActivity'])->name('debug-activity');
        
        // Grading Configuration Routes
        Route::post('/subjects/{subject}/grading-config', [App\Http\Controllers\GradeController::class, 'saveGradingConfig'])->name('save-grading-config');
        Route::post('/subjects/{subject}/final-rating-config', [App\Http\Controllers\GradeController::class, 'saveFinalRatingConfig'])->name('save-final-rating-config');
        
        // Export Routes
        Route::get('/subjects/{subject}/export/activities', [App\Http\Controllers\GradeController::class, 'exportActivities'])->name('export.activities');
        Route::get('/subjects/{subject}/export/pp', [App\Http\Controllers\GradeController::class, 'exportPP'])->name('export.pp');
        Route::get('/subjects/{subject}/export/term/{term}', [App\Http\Controllers\GradeController::class, 'exportTerm'])->name('export.term');
    });
    
    // Students Page (CSV Import + Auto-matching)
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [App\Http\Controllers\StudentController::class, 'index'])->name('index');
        Route::post('/upload-csv', [App\Http\Controllers\StudentController::class, 'uploadCsv'])->name('upload-csv');
    });
    
    // Dynamic Grading System
    Route::prefix('grading')->name('grading.')->group(function () {
        Route::get('/', [App\Http\Controllers\DynamicGradingController::class, 'index'])->name('index');
        Route::post('/add-class', [App\Http\Controllers\DynamicGradingController::class, 'addClass'])->name('add-class');
        Route::get('/{id}/configure', [App\Http\Controllers\DynamicGradingController::class, 'configure'])->name('configure');
        Route::post('/{id}/save-configuration', [App\Http\Controllers\DynamicGradingController::class, 'saveConfiguration'])->name('save-configuration');
        Route::get('/{id}/grade-sheet', [App\Http\Controllers\DynamicGradingController::class, 'gradeSheet'])->name('grade-sheet');
        Route::post('/component/{componentId}/add-item', [App\Http\Controllers\DynamicGradingController::class, 'addComponentItem'])->name('add-component-item');
        Route::post('/save-grade', [App\Http\Controllers\DynamicGradingController::class, 'saveGrade'])->name('save-grade');
    });
    
    // Student Mapping Page (Legacy - keep for now)
    Route::get('/student-mapping', [StudentMappingPageController::class, 'index'])->name('student-mapping.index');
    
    // Grade Matrix Routes
    Route::prefix('grade-matrix')->name('grade-matrix.')->group(function () {
        Route::get('/zero-based', [App\Http\Controllers\GradeMatrixController::class, 'zeroBased'])->name('zero-based');
        Route::get('/nursing', [App\Http\Controllers\GradeMatrixController::class, 'nursing'])->name('nursing');
        Route::get('/general-education', [App\Http\Controllers\GradeMatrixController::class, 'generalEducation'])->name('general-education');
        Route::get('/customized', [App\Http\Controllers\GradeMatrixController::class, 'customized'])->name('customized');
        
        // Term grades for each matrix type
        Route::get('/zero-based/subjects/{subject}/term/{term?}', [App\Http\Controllers\GradeMatrixController::class, 'zeroBasedTermGrades'])->name('zero-based.term');
        Route::get('/nursing/subjects/{subject}/term/{term?}', [App\Http\Controllers\GradeMatrixController::class, 'nursingTermGrades'])->name('nursing.term');
        Route::get('/general-education/subjects/{subject}/term/{term?}', [App\Http\Controllers\GradeMatrixController::class, 'generalEducationTermGrades'])->name('general-education.term');
        Route::get('/customized/subjects/{subject}/term/{term?}', [App\Http\Controllers\GradeMatrixController::class, 'customizedTermGrades'])->name('customized.term');
        
        // Full matrix for each type
        Route::get('/zero-based/subjects/{subject}/matrix', [App\Http\Controllers\GradeMatrixController::class, 'zeroBasedMatrix'])->name('zero-based.matrix');
        Route::get('/nursing/subjects/{subject}/matrix', [App\Http\Controllers\GradeMatrixController::class, 'nursingMatrix'])->name('nursing.matrix');
        Route::get('/general-education/subjects/{subject}/matrix', [App\Http\Controllers\GradeMatrixController::class, 'generalEducationMatrix'])->name('general-education.matrix');
        Route::get('/customized/subjects/{subject}/matrix', [App\Http\Controllers\GradeMatrixController::class, 'customizedMatrix'])->name('customized.matrix');
    });
    
    // Formula Configuration Routes (for Customized Matrix)
    Route::prefix('formula')->name('formula.')->group(function () {
        Route::get('/subjects/{subject}/term/{term}/config', [App\Http\Controllers\FormulaConfigController::class, 'show'])->name('config');
        Route::post('/subjects/{subject}/term/{term}/save', [App\Http\Controllers\FormulaConfigController::class, 'save'])->name('save');
        Route::post('/test', [App\Http\Controllers\FormulaConfigController::class, 'test'])->name('test');
    });
    
    // Nursing-specific routes
    Route::prefix('nursing')->name('nursing.')->group(function () {
        Route::get('/subjects/{subject}/comprehensive-exam', [App\Http\Controllers\ComprehensiveExamController::class, 'index'])->name('comprehensive-exam.index');
        Route::post('/subjects/{subject}/comprehensive-exam', [App\Http\Controllers\ComprehensiveExamController::class, 'store'])->name('comprehensive-exam.store');
        Route::put('/subjects/{subject}/comprehensive-exam/{studentMappingId}', [App\Http\Controllers\ComprehensiveExamController::class, 'update'])->name('comprehensive-exam.update');
        Route::delete('/subjects/{subject}/comprehensive-exam/{studentMappingId}', [App\Http\Controllers\ComprehensiveExamController::class, 'destroy'])->name('comprehensive-exam.destroy');
        Route::post('/subjects/{subject}/comprehensive-exam/import', [App\Http\Controllers\ComprehensiveExamController::class, 'import'])->name('comprehensive-exam.import');
        
        // Recalculate all nursing term grades for a subject
        Route::get('/subjects/{subject}/recalculate-grades', function(\App\Models\Subject $subject) {
            $gradeController = app(\App\Http\Controllers\GradeController::class);
            $reflection = new \ReflectionClass($gradeController);
            $recalculateMethod = $reflection->getMethod('recalculateClassStanding');
            $recalculateMethod->setAccessible(true);
            
            $terms = ['prelim', 'midterm', 'finals'];
            $recalculatedCount = 0;
            
            foreach ($terms as $term) {
                foreach ($subject->studentMappings as $studentMapping) {
                    $recalculateMethod->invoke($gradeController, $studentMapping, $term, 'nursing');
                    $recalculatedCount++;
                }
            }
            
            return redirect()->route('grade-matrix.nursing.matrix', $subject)
                ->with('success', "Successfully recalculated {$recalculatedCount} term grades using the nursing formula.");
        })->name('recalculate-grades');
    });
    
    Route::prefix('grade-matrix')->name('grade-matrix.')->group(function () {
        // Reset Zero-Based Formula to correct defaults
        Route::get('/zero-based/subjects/{subject}/reset-formula', function(\App\Models\Subject $subject) {
            $terms = ['prelim', 'midterm', 'finals'];
            
            foreach ($terms as $term) {
                \App\Models\GradingConfig::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'term' => $term,
                        'matrix_type' => 'zero-based'
                    ],
                    [
                        'class_standing_weight' => 40.00,
                        'exam_weight' => 60.00,
                        'formula_config' => [
                            'type' => 'percentage',
                            'components' => []
                        ]
                    ]
                );
            }
            
            return redirect()->route('grade-matrix.zero-based.term', ['subject' => $subject->id, 'term' => 'prelim'])
                ->with('success', 'Zero-Based formula reset to 40% CS + 60% Exam for all terms!');
        })->name('zero-based.reset-formula');
        
        // Fix General Education Formula (Web-based alternative to artisan command)
        Route::get('/general-education/subjects/{subject}/fix-formula', function(\App\Models\Subject $subject) {
            $terms = ['prelim', 'midterm', 'finals'];
            $updated = [];
            
            foreach ($terms as $term) {
                // Update or create grading config with matrix_type
                $gradingConfig = \App\Models\GradingConfig::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'term' => $term,
                        'matrix_type' => 'general-education'
                    ],
                    [
                        'class_standing_weight' => 66.67,
                        'exam_weight' => 33.33,
                        'formula_config' => [
                            'type' => 'transmuted',
                            'components' => []
                        ]
                    ]
                );
                $updated[] = $term;
            }
            
            // Now recalculate all grades with matrix_type
            $gradeController = app(\App\Http\Controllers\GradeController::class);
            foreach ($terms as $term) {
                $studentMappings = \App\Models\StudentMapping::where('subject_id', $subject->id)->get();
                foreach ($studentMappings as $studentMapping) {
                    // Use reflection to call private method with matrix_type
                    $reflection = new \ReflectionClass($gradeController);
                    $method = $reflection->getMethod('recalculateClassStanding');
                    $method->setAccessible(true);
                    $method->invoke($gradeController, $studentMapping, $term, 'general-education');
                    
                    // Recalculate term grade with matrix_type
                    $termGrade = \App\Models\TermGrade::where('student_mapping_id', $studentMapping->id)
                        ->where('subject_id', $subject->id)
                        ->where('term', $term)
                        ->first();
                    
                    if ($termGrade) {
                        $calculateMethod = $reflection->getMethod('calculateTermGrade');
                        $calculateMethod->setAccessible(true);
                        $calculateMethod->invoke($gradeController, $termGrade, 'general-education');
                    }
                }
            }
            
            return redirect()->route('grade-matrix.general-education.term', ['subject' => $subject->id, 'term' => 'prelim'])
                ->with('success', 'General Education formula applied and grades recalculated for all terms!');
        })->name('general-education.fix-formula');
    });
});
