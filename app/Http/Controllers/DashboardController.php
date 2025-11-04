<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Subject;
use App\Models\Activity;
use App\Models\StudentMapping;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats($faculty->id);
        
        // Get recent subjects
        $recentSubjects = Subject::where('faculty_id', $faculty->id)
            ->with(['activities'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get recent activities
        $recentActivities = Activity::whereHas('subject', function($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id);
            })
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get subjects that need attention (no activities, no student mappings, etc.)
        $subjectsNeedingAttention = $this->getSubjectsNeedingAttention($faculty->id);
        
        return view('dashboard', compact(
            'faculty',
            'stats', 
            'recentSubjects', 
            'recentActivities',
            'subjectsNeedingAttention'
        ));
    }
    
    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(int $facultyId): array
    {
        $totalSubjects = Subject::where('faculty_id', $facultyId)->count();
        
        $totalActivities = Activity::whereHas('subject', function($query) use ($facultyId) {
            $query->where('faculty_id', $facultyId);
        })->count();
        
        $connectedSubjects = Subject::where('faculty_id', $facultyId)
            ->whereNotNull('gcr_class_id')
            ->count();
        
        $totalMappings = StudentMapping::whereHas('subject', function($query) use ($facultyId) {
            $query->where('faculty_id', $facultyId);
        })->count();
        
        $mappedStudents = StudentMapping::whereHas('subject', function($query) use ($facultyId) {
            $query->where('faculty_id', $facultyId);
        })->whereNotNull('school_student_id')->count();
        
        $activitiesByTerm = Activity::whereHas('subject', function($query) use ($facultyId) {
            $query->where('faculty_id', $facultyId);
        })
        ->select('term', DB::raw('count(*) as count'))
        ->groupBy('term')
        ->pluck('count', 'term')
        ->toArray();
        
        return [
            'total_subjects' => $totalSubjects,
            'total_activities' => $totalActivities,
            'connected_subjects' => $connectedSubjects,
            'total_mappings' => $totalMappings,
            'mapped_students' => $mappedStudents,
            'unmapped_students' => $totalMappings - $mappedStudents,
            'activities_by_term' => $activitiesByTerm,
            'gcr_connection_rate' => $totalSubjects > 0 ? round(($connectedSubjects / $totalSubjects) * 100, 1) : 0,
            'mapping_completion_rate' => $totalMappings > 0 ? round(($mappedStudents / $totalMappings) * 100, 1) : 0
        ];
    }
    
    /**
     * Get subjects that need attention
     */
    private function getSubjectsNeedingAttention(int $facultyId): array
    {
        $subjects = Subject::where('faculty_id', $facultyId)
            ->with(['activities', 'studentMappings'])
            ->get();
        
        $needingAttention = [];
        
        foreach ($subjects as $subject) {
            $issues = [];
            
            // Check if subject has no activities
            if ($subject->activities->count() === 0) {
                $issues[] = 'No activities created';
            }
            
            // Check if subject is not connected to Google Classroom
            if (!$subject->gcr_class_id) {
                $issues[] = 'Not connected to Google Classroom';
            }
            
            // Check if subject has no student mappings
            if ($subject->studentMappings->count() === 0) {
                $issues[] = 'No student mappings';
            } else {
                // Check for unmapped students
                $unmappedCount = $subject->studentMappings()
                    ->whereNull('school_student_id')
                    ->count();
                if ($unmappedCount > 0) {
                    $issues[] = "{$unmappedCount} students not mapped";
                }
            }
            
            if (!empty($issues)) {
                $needingAttention[] = [
                    'subject' => $subject,
                    'issues' => $issues
                ];
            }
        }
        
        return $needingAttention;
    }
}
