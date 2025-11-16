@extends('layouts.admin')

@section('page-title', 'Grade Matrix - ' . $subject->subject_code)

@section('breadcrumbs')
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('subjects.index') }}" class="text-gray-500 hover:text-gray-700">Subjects</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('subjects.show', $subject) }}" class="text-gray-500 hover:text-gray-700">{{ $subject->subject_code }}</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li class="text-gray-900">Grade Matrix</li>
@endsection

@section('content')
<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">Grade Matrix</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $subject->subject_code }} - {{ $subject->subject_name }} 
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2" style="background-color: rgba(8, 105, 90, 0.1); color: #08695A;">
                        {{ $subject->section }}
                    </span>
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <a href="{{ route('grades.term', $subject) }}" 
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #08695A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#08695A';">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Term Grades
                </a>
                <a href="{{ route('grades.sync.index', $subject) }}" 
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #0A7B6A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#0A7B6A';">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Grade Sync
                </a>
                <button @click="$dispatch('open-export-modal')"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #E67E22;"
                   onmouseover="this.style.backgroundColor='#D35400';"
                   onmouseout="this.style.backgroundColor='#E67E22';">
                    <i class="fas fa-file-export mr-2"></i>
                    Export Grades
                </button>
                <a href="{{ route('subjects.show', $subject) }}" 
                   class="inline-flex items-center px-3 py-2 border text-sm leading-4 font-medium rounded-md bg-white transition-colors"
                   style="border-color: #08695A; color: #08695A;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Subject
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Bar -->
    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-6 text-sm">
            <div class="flex flex-wrap items-center gap-6">
                <div class="flex items-center">
                    <i class="fas fa-users text-gray-400 mr-2"></i>
                    <span class="text-gray-600">Students:</span>
                    <span class="font-semibold text-gray-900 ml-1">{{ $subject->studentMappings->count() }}</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-tasks text-gray-400 mr-2"></i>
                    <span class="text-gray-600">Activities:</span>
                    <span class="font-semibold text-gray-900 ml-1">{{ $subject->activities->count() }}</span>
                </div>
                @if($subject->activities->count() > 0)
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-gray-400 mr-2"></i>
                        <span class="text-gray-600">Terms:</span>
                        <span class="font-semibold text-gray-900 ml-1">{{ $activitiesByTerm->keys()->count() }}</span>
                    </div>
                @endif
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center text-xs" style="color: #08695A;">
                    <i class="fas fa-keyboard mr-2"></i>
                    <span>Press <kbd class="px-1 py-0.5 bg-gray-200 rounded text-xs">Enter</kbd> to save grades</span>
                </div>
                <button onclick="testGradeConnection()" class="text-xs px-2 py-1 rounded border" style="border-color: #08695A; color: #08695A;" title="Test server connection">
                    <i class="fas fa-wifi mr-1"></i>Test Connection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
@if($subject->studentMappings->isEmpty())
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">No Students Mapped</h3>
                <p class="mt-1 text-sm text-blue-700">
                    You need to map students to this subject before you can enter grades.
                </p>
                <div class="mt-3">
                    <a href="{{ route('mappings.index', $subject) }}" 
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-users mr-2"></i>
                        Map Students
                    </a>
                </div>
            </div>
        </div>
    </div>
@elseif($subject->activities->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-amber-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800">No Activities Created</h3>
                <p class="mt-1 text-sm text-amber-700">
                    You need to create activities before you can enter grades.
                </p>
                <div class="mt-3">
                    <a href="{{ route('activities.create') }}?subject_id={{ $subject->id }}" 
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-amber-700 bg-amber-100 hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        <i class="fas fa-plus mr-2"></i>
                        Add Activities
                    </a>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Grade Matrix Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto" style="max-height: 75vh;">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <!-- Term Headers Row -->
                    <tr>
                        <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" 
                            style="min-width: 200px;">
                            Student Information
                        </th>
                        @foreach(['prelim', 'midterm', 'finals'] as $term)
                            @if(isset($activitiesByTerm[$term]))
                                @php
                                    $termActivities = $activitiesByTerm[$term];
                                    $lectureActivities = $termActivities->where('type', 'lecture');
                                    $labActivities = $termActivities->where('type', 'lab');
                                    $totalActivities = $lectureActivities->count() + ($subject->type === 'lecture_lab' ? $labActivities->count() : 0);
                                @endphp
                                
                                @if($totalActivities > 0)
                                    <th colspan="{{ $totalActivities }}" 
                                        class="px-4 py-3 text-center text-sm font-semibold text-gray-900 uppercase tracking-wider border-l border-gray-300
                                               {{ $term === 'prelim' ? 'bg-blue-100 text-blue-900' : ($term === 'midterm' ? 'bg-green-100 text-green-900' : 'bg-purple-100 text-purple-900') }}">
                                        <div class="flex items-center justify-center">
                                            <i class="fas fa-{{ $term === 'prelim' ? 'play' : ($term === 'midterm' ? 'pause' : 'stop') }} mr-2"></i>
                                            {{ ucfirst($term) }} Term
                                        </div>
                                    </th>
                                @endif
                            @endif
                        @endforeach
                    </tr>
                    
                    <!-- Activity Type Headers Row -->
                    <tr>
                        <th class="sticky left-0 z-20 bg-gray-50 px-4 py-2 text-left text-xs font-medium text-gray-500 border-r border-gray-200">
                            Name / Email
                        </th>
                        @foreach(['prelim', 'midterm', 'finals'] as $term)
                            @if(isset($activitiesByTerm[$term]))
                                @php
                                    $termActivities = $activitiesByTerm[$term];
                                    $lectureActivities = $termActivities->where('type', 'lecture');
                                    $labActivities = $termActivities->where('type', 'lab');
                                @endphp
                                
                                @if($lectureActivities->isNotEmpty())
                                    @foreach($lectureActivities as $activity)
                                        <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border-l border-gray-200 bg-blue-50" 
                                            style="min-width: 90px;" title="{{ $activity->name }}">
                                            <div class="space-y-1">
                                                <div class="font-semibold text-blue-900">LEC</div>
                                                <div class="text-xs text-gray-600 leading-tight">{{ Str::limit($activity->name, 12) }}</div>
                                                <div class="text-xs text-blue-700 font-medium">/ {{ $activity->max_score }}</div>
                                            </div>
                                        </th>
                                    @endforeach
                                @endif
                                
                                @if($subject->type === 'lecture_lab' && $labActivities->isNotEmpty())
                                    @foreach($labActivities as $activity)
                                        <th class="px-2 py-2 text-center text-xs font-medium text-gray-700 border-l border-gray-200 bg-pink-50" 
                                            style="min-width: 90px;" title="{{ $activity->name }}">
                                            <div class="space-y-1">
                                                <div class="font-semibold text-pink-900">LAB</div>
                                                <div class="text-xs text-gray-600 leading-tight">{{ Str::limit($activity->name, 12) }}</div>
                                                <div class="text-xs text-pink-700 font-medium">/ {{ $activity->max_score }}</div>
                                            </div>
                                        </th>
                                    @endforeach
                                @endif
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($subject->studentMappings as $index => $studentMapping)
                        <tr class="hover:bg-gray-50 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-25' }}">
                            <td class="sticky left-0 z-10 bg-inherit px-4 py-3 border-r border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-semibold" style="background: linear-gradient(135deg, #08695A, #0A7B6A);">
                                            {{ substr($studentMapping->student_name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $studentMapping->student_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 truncate">
                                            {{ $studentMapping->student_email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            @foreach(['prelim', 'midterm', 'finals'] as $term)
                                @if(isset($activitiesByTerm[$term]))
                                    @php
                                        $termActivities = $activitiesByTerm[$term];
                                        $lectureActivities = $termActivities->where('type', 'lecture');
                                        $labActivities = $termActivities->where('type', 'lab');
                                    @endphp
                                    
                                    @foreach($lectureActivities as $activity)
                                        @php
                                            $gradeRecord = $gradeMatrix[$studentMapping->id][$activity->id] ?? null;
                                        @endphp
                                        <td class="px-2 py-3 text-center border-l border-gray-200 bg-blue-25">
                                            <div class="space-y-1">
                                                <input 
                                                    type="number" 
                                                    class="w-16 px-2 py-1 text-sm text-center border border-gray-300 rounded-md grade-input transition-colors"
                                                    style="focus:ring-color: #08695A; focus:border-color: #08695A;"
                                                    onfocus="this.style.borderColor='#08695A'; this.style.boxShadow='0 0 0 2px rgba(8, 105, 90, 0.2)';"
                                                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                                                    data-student-mapping-id="{{ $studentMapping->id }}"
                                                    data-activity-id="{{ $activity->id }}"
                                                    data-max-score="{{ $activity->max_score }}"
                                                    value="{{ $gradeRecord?->score }}"
                                                    min="0"
                                                    max="{{ $activity->max_score }}"
                                                    step="0.01"
                                                    placeholder="0"
                                                >
                                                @if($gradeRecord?->percentage)
                                                    <div class="text-xs font-medium percentage-display" style="color: #08695A;">
                                                        {{ number_format($gradeRecord->percentage, 1) }}%
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                    
                                    @if($subject->type === 'lecture_lab')
                                        @foreach($labActivities as $activity)
                                            @php
                                                $gradeRecord = $gradeMatrix[$studentMapping->id][$activity->id] ?? null;
                                            @endphp
                                            <td class="px-2 py-3 text-center border-l border-gray-200 bg-pink-25">
                                                <div class="space-y-1">
                                                    <input 
                                                        type="number" 
                                                        class="w-16 px-2 py-1 text-sm text-center border border-gray-300 rounded-md grade-input transition-colors"
                                                        style="focus:ring-color: #08695A; focus:border-color: #08695A;"
                                                        onfocus="this.style.borderColor='#08695A'; this.style.boxShadow='0 0 0 2px rgba(8, 105, 90, 0.2)';"
                                                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                                                        data-student-mapping-id="{{ $studentMapping->id }}"
                                                        data-activity-id="{{ $activity->id }}"
                                                        data-max-score="{{ $activity->max_score }}"
                                                        value="{{ $gradeRecord?->score }}"
                                                        min="0"
                                                        max="{{ $activity->max_score }}"
                                                        step="0.01"
                                                        placeholder="0"
                                                    >
                                                    @if($gradeRecord?->percentage)
                                                        <div class="text-xs font-medium percentage-display" style="color: #08695A;">
                                                            {{ number_format($gradeRecord->percentage, 1) }}%
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Loading Overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
        <span class="text-gray-700 font-medium">Saving grade...</span>
    </div>
</div>

<!-- Success Toast -->
<div id="success-toast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span>Grade saved successfully!</span>
    </div>
</div>

<!-- Error Toast -->
<div id="error-toast" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span id="error-message">Failed to save grade!</span>
    </div>
</div>

<!-- Export Modal -->
<div x-data="{ open: false }" 
     @open-export-modal.window="open = true"
     x-show="open" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="open" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             @click="open = false"></div>

        <!-- Modal panel -->
        <div x-show="open" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-file-export text-orange-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Export Grades
                        </h3>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 mb-4">
                                Choose what you want to export for <strong>{{ $subject->subject_code }}</strong>
                            </p>
                            
                            <!-- Export Options -->
                            <div class="space-y-3">
                                <!-- Export Activities + Exam -->
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer"
                                     onclick="window.location.href='{{ route('grades.export.activities', $subject) }}'">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-tasks text-blue-500 text-xl"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="text-sm font-medium text-gray-900">Activities + Exam</h4>
                                            <p class="text-xs text-gray-500 mt-1">Export all activities and exam scores</p>
                                        </div>
                                        <i class="fas fa-chevron-right text-gray-400"></i>
                                    </div>
                                </div>

                                <!-- Export Grade (PP) -->
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer"
                                     onclick="window.location.href='{{ route('grades.export.pp', $subject) }}'">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-percentage text-green-500 text-xl"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="text-sm font-medium text-gray-900">Grade (PP)</h4>
                                            <p class="text-xs text-gray-500 mt-1">Export computed grades in percentage</p>
                                        </div>
                                        <i class="fas fa-chevron-right text-gray-400"></i>
                                    </div>
                                </div>

                                <!-- Term-Based Grading -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-start mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-calendar-alt text-purple-500 text-xl"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="text-sm font-medium text-gray-900">Term-Based Grading</h4>
                                            <p class="text-xs text-gray-500 mt-1">Export grades by term</p>
                                        </div>
                                    </div>
                                    <div class="ml-8 space-y-2">
                                        <a href="{{ route('grades.export.term', ['subject' => $subject, 'term' => 'prelim']) }}"
                                           class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                            Prelim Term
                                        </a>
                                        <a href="{{ route('grades.export.term', ['subject' => $subject, 'term' => 'midterm']) }}"
                                           class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                            Midterm Term
                                        </a>
                                        <a href="{{ route('grades.export.term', ['subject' => $subject, 'term' => 'finals']) }}"
                                           class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                            Finals Term
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        @click="open = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Custom background colors for better visual separation */
.bg-gray-25 {
    background-color: #fafafa;
}

.bg-blue-25 {
    background-color: #f8faff;
}

.bg-pink-25 {
    background-color: #fef7f7;
}

/* Grade input states */
.grade-input.pending {
    @apply bg-blue-50 border-blue-300 ring-1 ring-blue-200;
    position: relative;
}

.grade-input.pending::after {
    content: "Press Enter to save";
    position: absolute;
    top: -25px;
    left: 0;
    font-size: 10px;
    color: #08695A;
    background: white;
    padding: 2px 4px;
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    white-space: nowrap;
    z-index: 10;
}

.grade-input.saving {
    @apply bg-yellow-50 border-yellow-300 ring-2 ring-yellow-200;
}

.grade-input.saved {
    @apply bg-green-50 border-green-300 ring-2 ring-green-200;
}

.grade-input.error {
    @apply bg-red-50 border-red-300 ring-2 ring-red-200;
}

/* Smooth transitions for input states */
.grade-input {
    transition: all 0.2s ease-in-out;
}

/* Custom scrollbar for better UX */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Sticky positioning improvements */
.sticky {
    position: sticky;
}

/* Animation for toasts */
.toast-enter {
    transform: translateX(100%);
}

.toast-show {
    transform: translateX(0);
}

.toast-exit {
    transform: translateX(100%);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .grade-input {
        @apply w-12 text-xs;
    }
    
    th[style*="min-width: 90px"] {
        min-width: 70px !important;
    }
    
    .sticky.left-0 {
        min-width: 160px !important;
    }
}

@media (max-width: 640px) {
    .grade-input {
        @apply w-10;
    }
    
    th[style*="min-width: 90px"] {
        min-width: 60px !important;
    }
    
    .sticky.left-0 {
        min-width: 140px !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const gradeInputs = document.querySelectorAll('.grade-input');
    const loadingOverlay = document.getElementById('loading-overlay');
    const successToast = document.getElementById('success-toast');
    const errorToast = document.getElementById('error-toast');
    const errorMessage = document.getElementById('error-message');
    
    let saveTimeouts = new Map();
    let activeRequests = new Set();

    // Initialize grade inputs
    gradeInputs.forEach(input => {
        const key = `${input.dataset.studentMappingId}-${input.dataset.activityId}`;
        
        // Remove auto-save on input, only save on Enter or blur
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveGrade(this, key);
            }
        });

        input.addEventListener('blur', function() {
            // Only save if there's been a change and no active request
            if (this.dataset.hasChanged === 'true' && !activeRequests.has(key)) {
                saveGrade(this, key);
            }
        });

        // Track changes without auto-saving
        input.addEventListener('input', function() {
            this.dataset.hasChanged = 'true';
            // Clear any existing timeout
            if (saveTimeouts.has(key)) {
                clearTimeout(saveTimeouts.get(key));
                saveTimeouts.delete(key);
            }
            // Remove any previous states and add pending state
            this.classList.remove('saved', 'error', 'saving');
            this.classList.add('pending');
        });
    });

    // Remove the old handleGradeInput and handleGradeBlur functions as they're no longer needed

    function saveGrade(input, key) {
        // Prevent duplicate requests
        if (activeRequests.has(key)) {
            return;
        }

        const studentMappingId = input.dataset.studentMappingId;
        const activityId = input.dataset.activityId;
        const score = input.value.trim() || null;
        const maxScore = parseFloat(input.dataset.maxScore);

        // Validate score
        if (score !== null) {
            const numericScore = parseFloat(score);
            if (isNaN(numericScore) || numericScore < 0 || numericScore > maxScore) {
                input.classList.remove('saving');
                input.classList.add('error');
                showErrorToast(`Score must be between 0 and ${maxScore}`);
                return;
            }
        }

        // Mark request as active
        activeRequests.add(key);
        
        // Add saving state
        input.classList.remove('saved', 'error', 'pending');
        input.classList.add('saving');
        
        // Show loading overlay
        loadingOverlay.classList.remove('hidden');

        fetch('{{ route("grades.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                student_mapping_id: studentMappingId,
                activity_id: activityId,
                score: score
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Response text:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                handleSaveSuccess(input, data);
                showSuccessToast();
            } else {
                handleSaveError(input, data.error || 'Failed to save grade');
            }
        })
        .catch(error => {
            console.error('Error saving grade:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            
            let errorMessage = 'Network error. Please check your connection.';
            if (error.message.includes('HTTP')) {
                errorMessage = `Server error: ${error.message}`;
            } else if (error.name === 'TypeError' || error.message.includes('Failed to fetch')) {
                errorMessage = 'Connection failed. Please ensure the Laravel server is running (php artisan serve).';
            }
            
            handleSaveError(input, errorMessage);
        })
        .finally(() => {
            // Clean up
            activeRequests.delete(key);
            loadingOverlay.classList.add('hidden');
        });
    }

    function handleSaveSuccess(input, data) {
        input.classList.remove('saving', 'error', 'pending');
        input.classList.add('saved');
        input.dataset.hasChanged = 'false';
        
        // Update percentage display
        updatePercentageDisplay(input, data.grade_record.percentage);
        
        // Remove saved state after 2 seconds
        setTimeout(() => {
            input.classList.remove('saved');
        }, 2000);
    }

    function handleSaveError(input, errorMsg) {
        input.classList.remove('saving', 'pending');
        input.classList.add('error');
        input.dataset.hasChanged = 'false'; // Reset change flag
        showErrorToast(errorMsg);
        
        // Remove error state after 3 seconds
        setTimeout(() => {
            input.classList.remove('error');
        }, 3000);
    }

    function updatePercentageDisplay(input, percentage) {
        const container = input.parentElement;
        let percentageDisplay = container.querySelector('.percentage-display');
        
        if (percentage !== null && percentage !== undefined) {
            if (percentageDisplay) {
                percentageDisplay.textContent = percentage.toFixed(1) + '%';
            } else {
                percentageDisplay = document.createElement('div');
                percentageDisplay.className = 'text-xs font-medium percentage-display';
                
                // Set color to main theme color
                percentageDisplay.style.color = '#08695A';
                
                percentageDisplay.textContent = percentage.toFixed(1) + '%';
                container.appendChild(percentageDisplay);
            }
        } else if (percentageDisplay) {
            percentageDisplay.remove();
        }
    }

    function showSuccessToast() {
        successToast.classList.remove('translate-x-full');
        setTimeout(() => {
            successToast.classList.add('translate-x-full');
        }, 3000);
    }

    function showErrorToast(message) {
        errorMessage.textContent = message;
        errorToast.classList.remove('translate-x-full');
        setTimeout(() => {
            errorToast.classList.add('translate-x-full');
        }, 5000);
    }

    // Test server connectivity on page load
    function testServerConnection() {
        fetch(window.location.origin + '/test-auth', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                console.log('✅ Server connection successful');
            } else {
                console.warn('⚠️ Server responded with status:', response.status);
            }
        })
        .catch(error => {
            console.error('❌ Server connection failed:', error);
            showErrorToast('Warning: Cannot connect to server. Please check if the development server is running.');
        });
    }

    // Test connection on page load
    testServerConnection();

    // Manual connection test function
    window.testGradeConnection = function() {
        showSuccessToast('Testing connection...');
        
        fetch('{{ route("grades.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                student_mapping_id: 999999, // Invalid ID for testing
                activity_id: 999999,
                score: 0
            })
        })
        .then(response => {
            console.log('Test response status:', response.status);
            if (response.status === 422) {
                showSuccessToast('✅ Server connection working! (Validation error expected)');
            } else if (response.ok) {
                showSuccessToast('✅ Server connection working!');
            } else {
                showErrorToast(`⚠️ Server responded with status: ${response.status}`);
            }
        })
        .catch(error => {
            console.error('Connection test failed:', error);
            showErrorToast('❌ Connection test failed: ' + error.message);
        });
    };

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            // Save all modified grades
            gradeInputs.forEach(input => {
                if (input.classList.contains('saving')) {
                    const key = `${input.dataset.studentMappingId}-${input.dataset.activityId}`;
                    saveGrade(input, key);
                }
            });
        }
    });
});
</script>
@endpush