@extends('layouts.admin')

@section('page-title', ucfirst($term) . ' Grades - Archived')

@section('content')
@php
    // Get activities for this term
    $lectureActivities = $subject->activities->where('term', $term)->where('type', 'lecture');
    $labActivities = $subject->activities->where('term', $term)->where('type', 'lab');
    
    // Get grading class for this term
    $gradingClass = $subject->gradingClasses->where('term', $term)->first();
    
    // Get all grades for this term with component items loaded
    $termGrades = [];
    if ($gradingClass) {
        // Load component items with their grades for better performance
        $gradingClass->load(['components.items.grades' => function($query) use ($subject) {
            $query->whereIn('student_mapping_id', $subject->studentMappings->pluck('id'));
        }]);
        
        foreach ($subject->studentMappings as $student) {
            $grades = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                ->where('student_mapping_id', $student->id)
                ->with('componentItem.activity')
                ->get();
            $termGrades[$student->id] = $grades;
        }
    }
@endphp

<div class="container mx-auto px-4 py-6">
    <!-- Archived Notice -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-lg">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-archive text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>This subject is archived.</strong> Grades are read-only. You can view and export grades but cannot make changes.
                </p>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ ucfirst($term) }} Term Grades - Archived</h1>
                <p class="text-gray-600">{{ $subject->subject_code }} - {{ $subject->subject_name }} ({{ $subject->section }})</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('archive.show', $subject) }}" 
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #08695A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#08695A';">
                    <i class="fas fa-table mr-2"></i>
                    Full Matrix
                </a>
                <a href="{{ route('archive.index') }}" 
                   class="inline-flex items-center px-3 py-2 border text-sm leading-4 font-medium rounded-md bg-white transition-colors"
                   style="border-color: #08695A; color: #08695A;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Archives
                </a>
            </div>
        </div>
    </div>

    <!-- Term Navigation Tabs -->
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                @foreach(['prelim', 'midterm', 'finals'] as $termKey)
                    <a href="{{ route('grades.term', [$subject, $termKey]) }}"
                       class="py-4 px-4 border-b-2 font-medium text-sm transition-colors {{ $term === $termKey ? 'text-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                       style="{{ $term === $termKey ? 'border-color: #08695A; background-color: #08695A; border-radius: 8px 8px 0 0; margin-bottom: -1px;' : '' }}">
                        <span class="capitalize">{{ $termKey }}</span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Archived
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    @if($subject->studentMappings->isEmpty())
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                    <i class="fas fa-users text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Students</h3>
                <p class="text-gray-500">No students were mapped to this subject before it was archived.</p>
            </div>
        </div>
    @elseif($lectureActivities->isEmpty() && $labActivities->isEmpty())
        <!-- No Activities -->
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                    <i class="fas fa-tasks text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Activities</h3>
                <p class="text-gray-500">No activities were created for the {{ $term }} term before this subject was archived.</p>
            </div>
        </div>
    @else
        <!-- Activities and Grades Table -->
        <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100 sticky left-0 z-10 min-w-48">
                            Student Name
                        </th>
                        @if($lectureActivities->isNotEmpty())
                            <th colspan="{{ $lectureActivities->count() }}" class="px-6 py-3 text-center text-xs font-medium text-blue-700 uppercase tracking-wider bg-blue-50 border-l border-blue-200">
                                Lecture Activities
                            </th>
                        @endif
                        @if($labActivities->isNotEmpty() && $subject->type === 'lecture_lab')
                            <th colspan="{{ $labActivities->count() }}" class="px-6 py-3 text-center text-xs font-medium text-purple-700 uppercase tracking-wider bg-purple-50 border-l border-purple-200">
                                Laboratory Activities
                            </th>
                        @endif
                        @if($gradingClass)
                            <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-teal-50 border-l border-teal-200 min-w-32">
                                Class Standing
                            </th>
                            <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50 border-l border-yellow-200 min-w-32">
                                Exam Score
                            </th>
                            <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-orange-50 border-l border-orange-200 min-w-32">
                                Exam Grade
                            </th>
                            <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-red-50 border-l border-red-200 min-w-32">
                                <span class="capitalize">{{ $term }}</span> Grade
                            </th>
                        @endif
                    </tr>
                    <tr>
                        @foreach($lectureActivities as $activity)
                            <th class="px-3 py-2 text-center text-xs font-medium text-blue-600 bg-blue-25 border-l border-blue-100 min-w-20" title="{{ $activity->name }}">
                                <div class="font-semibold">{{ Str::limit($activity->name, 8) }}</div>
                                <div class="text-xs text-gray-500 mt-1">/{{ $activity->max_score == floor($activity->max_score) ? intval($activity->max_score) : $activity->max_score }}</div>
                            </th>
                        @endforeach
                        @if($subject->type === 'lecture_lab')
                            @foreach($labActivities as $activity)
                                <th class="px-3 py-2 text-center text-xs font-medium text-purple-600 bg-purple-25 border-l border-purple-100 min-w-20" title="{{ $activity->name }}">
                                    <div class="font-semibold">{{ Str::limit($activity->name, 8) }}</div>
                                    <div class="text-xs text-gray-500 mt-1">/{{ $activity->max_score == floor($activity->max_score) ? intval($activity->max_score) : $activity->max_score }}</div>
                                </th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($subject->studentMappings as $studentMapping)
                        @php
                            $studentGrades = $termGrades[$studentMapping->id] ?? collect();
                            
                            // Calculate class standing average
                            $activityGrades = $studentGrades->whereNotNull('computed_score');
                            $classStanding = $activityGrades->isNotEmpty() ? $activityGrades->avg('computed_score') : null;
                            
                            // Calculate term grade using proper component weights and computed scores
                            $termGradeValue = null;
                            if ($gradingClass && $gradingClass->components->isNotEmpty()) {
                                $termGradeComponents = [];
                                
                                foreach ($gradingClass->components as $component) {
                                    if ($component->component_type === 'exam') {
                                        // Use computed exam score
                                        $examGrade = $studentGrades->where('component_id', $component->id)->whereNotNull('exam_score')->first();
                                        if ($examGrade && $examGrade->computed_score !== null) {
                                            $termGradeComponents[] = $examGrade->computed_score * ($component->weight_percentage / 100);
                                        }
                                    } else {
                                        // Use average of computed scores for regular components
                                        $componentGrades = $studentGrades->whereIn('component_item_id', $component->items->pluck('id'))
                                            ->whereNotNull('computed_score');
                                        
                                        if ($componentGrades->isNotEmpty()) {
                                            $componentAvg = $componentGrades->avg('computed_score');
                                            $termGradeComponents[] = $componentAvg * ($component->weight_percentage / 100);
                                        }
                                    }
                                }
                                
                                if (!empty($termGradeComponents)) {
                                    $termGradeValue = array_sum($termGradeComponents);
                                }
                            }
                            
                            // Fallback calculation for display purposes
                            $examGrade = $studentGrades->whereNotNull('exam_score')->first();
                            $examScore = $examGrade ? $examGrade->exam_score : null;
                            $examMaxScore = $gradingClass && $gradingClass->components->where('component_type', 'exam')->first() 
                                ? $gradingClass->components->where('component_type', 'exam')->first()->exam_max_score ?? 100 
                                : 100;
                            $examGradeValue = $examGrade && $examGrade->computed_score !== null ? $examGrade->computed_score : null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap bg-gray-50 sticky left-0 z-10 border-r border-gray-200">
                                <div class="text-sm font-medium text-gray-900">{{ $studentMapping->formatted_name }}</div>
                                @if($studentMapping->student_email)
                                    <div class="text-xs text-gray-500">{{ $studentMapping->student_email }}</div>
                                @endif
                            </td>
                            
                            <!-- Lecture Activity Scores -->
                            @foreach($lectureActivities as $activity)
                                @php
                                    // Find the grade through component item that matches this activity
                                    $activityGrade = $studentGrades->filter(function($grade) use ($activity) {
                                        return $grade->componentItem && 
                                               $grade->componentItem->activity_id == $activity->id;
                                    })->first();
                                    $score = $activityGrade ? $activityGrade->score : null;
                                    $computedScore = $activityGrade ? $activityGrade->computed_score : null;
                                @endphp
                                <td class="px-3 py-4 text-center text-sm bg-blue-25 border-l border-blue-100">
                                    @if($score !== null)
                                        <div class="font-medium text-blue-900">{{ $score }}</div>
                                        @if($computedScore !== null && $computedScore != $score)
                                            <div class="text-xs text-blue-600 mt-1">({{ number_format($computedScore, 2) }})</div>
                                        @endif
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            
                            <!-- Lab Activity Scores -->
                            @if($subject->type === 'lecture_lab')
                                @foreach($labActivities as $activity)
                                    @php
                                        // Find the grade through component item that matches this activity
                                        $activityGrade = $studentGrades->filter(function($grade) use ($activity) {
                                            return $grade->componentItem && 
                                                   $grade->componentItem->activity_id == $activity->id;
                                        })->first();
                                        $score = $activityGrade ? $activityGrade->score : null;
                                        $computedScore = $activityGrade ? $activityGrade->computed_score : null;
                                    @endphp
                                    <td class="px-3 py-4 text-center text-sm bg-purple-25 border-l border-purple-100">
                                        @if($score !== null)
                                            <div class="font-medium text-purple-900">{{ $score }}</div>
                                            @if($computedScore !== null && $computedScore != $score)
                                                <div class="text-xs text-purple-600 mt-1">({{ number_format($computedScore, 2) }})</div>
                                            @endif
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                            
                            @if($gradingClass)
                                <!-- Class Standing -->
                                <td class="px-6 py-4 text-center text-sm bg-teal-25 border-l border-teal-200">
                                    @if($classStanding !== null)
                                        <span class="font-medium text-teal-900">{{ number_format($classStanding, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <!-- Exam Score -->
                                <td class="px-6 py-4 text-center text-sm bg-yellow-25 border-l border-yellow-200">
                                    @if($examScore !== null)
                                        <span class="font-medium text-yellow-900">{{ $examScore }}/{{ $examMaxScore }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <!-- Exam Grade -->
                                <td class="px-6 py-4 text-center text-sm bg-orange-25 border-l border-orange-200">
                                    @if($examGradeValue !== null)
                                        <span class="font-medium text-orange-900">{{ number_format($examGradeValue, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <!-- Term Grade -->
                                <td class="px-6 py-4 text-center text-sm bg-red-25 border-l border-red-200">
                                    @if($termGradeValue !== null)
                                        <span class="font-bold text-red-900">{{ number_format($termGradeValue, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Export Actions -->
        <div class="mt-6 bg-white rounded-lg shadow-sm border p-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Export {{ ucfirst($term) }} Term</h3>
            <div class="flex gap-4">
                <button onclick="exportTermPDF()" 
                   class="flex items-center px-6 py-3 border border-gray-300 rounded-lg hover:bg-red-50 hover:border-red-300 transition-colors">
                    <i class="fas fa-file-pdf text-red-500 text-xl mr-3"></i>
                    <div>
                        <div class="font-medium text-gray-900">Export as PDF</div>
                        <div class="text-sm text-gray-500">{{ ucfirst($term) }} term report</div>
                    </div>
                </button>
                
                <button onclick="exportTermCSV()" 
                   class="flex items-center px-6 py-3 border border-gray-300 rounded-lg hover:bg-green-50 hover:border-green-300 transition-colors">
                    <i class="fas fa-file-csv text-green-500 text-xl mr-3"></i>
                    <div>
                        <div class="font-medium text-gray-900">Export as CSV</div>
                        <div class="text-sm text-gray-500">Spreadsheet format</div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Export Script -->
        <script>
        function exportTermPDF() {
            const deanName = prompt('Enter Dean\'s name for the PDF signature:', '');
            if (deanName !== null) {
                window.location.href = '{{ route('archive.export-term-pdf', [$subject, $term]) }}?dean_name=' + encodeURIComponent(deanName);
            }
        }
        
        function exportTermCSV() {
            // For now, redirect to full matrix CSV - we can create term-specific CSV later if needed
            window.location.href = '{{ route('grading.export-full-matrix-csv', $subject->id) }}';
        }
        </script>
    @endif
</div>
@endsection
