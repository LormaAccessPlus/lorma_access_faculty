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
                <h1 class="text-2xl font-bold text-gray-900">Grade Matrix - Term Grades</h1>
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
                    <i class="fas fa-edit mr-2"></i>
                    Edit Term Grades
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
    <div class="px-6 py-3 bg-gray-50">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <div class="flex items-center">
                <i class="fas fa-users text-gray-400 mr-2"></i>
                <span class="text-gray-600">Students:</span>
                <span class="font-semibold text-gray-900 ml-1">{{ $subject->studentMappings->count() }}</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-chart-line text-gray-400 mr-2"></i>
                <span class="text-gray-600">Showing computed term grades</span>
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
                    You need to map students to this subject before you can view grades.
                </p>
                <div class="mt-3">
                    <a href="{{ route('mappings.index', $subject) }}" 
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                        <i class="fas fa-users mr-2"></i>
                        Map Students
                    </a>
                </div>
            </div>
        </div>
    </div>
@else
    @php
        // Get all term grades for this subject
        $termGrades = \App\Models\TermGrade::where('subject_id', $subject->id)
            ->with('studentMapping')
            ->get()
            ->groupBy('student_mapping_id');
        
        // Get final rating configuration
        $finalRatingConfig = $subject->final_rating_config ?? [
            'prelim_weight' => 30,
            'midterm_weight' => 30,
            'finals_weight' => 40
        ];
        
        // Calculate final grades using the configured weights
        $finalGrades = [];
        foreach($subject->studentMappings as $studentMapping) {
            $studentTermGrades = $termGrades->get($studentMapping->id, collect());
            $prelim = $studentTermGrades->where('term', 'prelim')->first()?->term_grade;
            $midterm = $studentTermGrades->where('term', 'midterm')->first()?->term_grade;
            $finals = $studentTermGrades->where('term', 'finals')->first()?->term_grade;
            
            // Calculate final grade if all terms are available using the formula:
            // Final Rating = (Prelim × prelim_weight%) + (Midterm × midterm_weight%) + (Finals × finals_weight%)
            if ($prelim !== null && $midterm !== null && $finals !== null) {
                $finalRating = ($prelim * ($finalRatingConfig['prelim_weight'] / 100)) +
                              ($midterm * ($finalRatingConfig['midterm_weight'] / 100)) +
                              ($finals * ($finalRatingConfig['finals_weight'] / 100));
                $finalGrades[$studentMapping->id] = round($finalRating);
            } else {
                $finalGrades[$studentMapping->id] = null;
            }
        }
    @endphp

    <!-- Grade Matrix Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" 
                            style="min-width: 250px;">
                            Student Information
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-medium uppercase tracking-wider bg-blue-50 text-blue-900 border-l border-gray-300">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-play mb-1"></i>
                                <span>Prelim</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-medium uppercase tracking-wider bg-green-50 text-green-900 border-l border-gray-300">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-pause mb-1"></i>
                                <span>Midterm</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-medium uppercase tracking-wider bg-purple-50 text-purple-900 border-l border-gray-300">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-stop mb-1"></i>
                                <span>Finals</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-medium uppercase tracking-wider bg-teal-50 text-teal-900 border-l border-gray-300">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-trophy mb-1"></i>
                                <span>Final Grade</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($subject->studentMappings as $index => $studentMapping)
                        @php
                            $studentTermGrades = $termGrades->get($studentMapping->id, collect());
                            $prelimGrade = $studentTermGrades->where('term', 'prelim')->first();
                            $midtermGrade = $studentTermGrades->where('term', 'midterm')->first();
                            $finalsGrade = $studentTermGrades->where('term', 'finals')->first();
                            $finalGrade = $finalGrades[$studentMapping->id];
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-25' }}">
                            <td class="sticky left-0 z-10 bg-inherit px-6 py-4 border-r border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold" 
                                             style="background: linear-gradient(135deg, #08695A, #0A7B6A);">
                                            {{ substr($studentMapping->student_name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $studentMapping->student_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $studentMapping->student_email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Prelim Grade -->
                            <td class="px-6 py-4 text-center border-l border-gray-200 bg-blue-25">
                                @if($prelimGrade && $prelimGrade->term_grade !== null)
                                    <div class="space-y-1">
                                        <div class="text-2xl font-bold text-blue-900">
                                            {{ round($prelimGrade->term_grade) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            CS: {{ round($prelimGrade->class_standing) }} | 
                                            Exam: {{ round($prelimGrade->exam_score ?? 0) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                            
                            <!-- Midterm Grade -->
                            <td class="px-6 py-4 text-center border-l border-gray-200 bg-green-25">
                                @if($midtermGrade && $midtermGrade->term_grade !== null)
                                    <div class="space-y-1">
                                        <div class="text-2xl font-bold text-green-900">
                                            {{ round($midtermGrade->term_grade) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            CS: {{ round($midtermGrade->class_standing) }} | 
                                            Exam: {{ round($midtermGrade->exam_score ?? 0) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                            
                            <!-- Finals Grade -->
                            <td class="px-6 py-4 text-center border-l border-gray-200 bg-purple-25">
                                @if($finalsGrade && $finalsGrade->term_grade !== null)
                                    <div class="space-y-1">
                                        <div class="text-2xl font-bold text-purple-900">
                                            {{ round($finalsGrade->term_grade) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            CS: {{ round($finalsGrade->class_standing) }} | 
                                            Exam: {{ round($finalsGrade->exam_score ?? 0) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                            
                            <!-- Final Grade -->
                            <td class="px-6 py-4 text-center border-l border-gray-200 bg-teal-25">
                                @if($finalGrade !== null)
                                    <div class="space-y-1">
                                        <div class="text-3xl font-bold text-teal-900">
                                            {{ round($finalGrade) }}
                                        </div>
                                        <div class="text-xs font-medium" style="color: #08695A;">
                                            @if($finalGrade >= 75)
                                                <i class="fas fa-check-circle mr-1"></i>PASSED
                                            @else
                                                <i class="fas fa-times-circle mr-1"></i>FAILED
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legend -->
    <div class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6 text-sm">
                <div class="flex items-center">
                    <span class="text-gray-600">CS = Class Standing</span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-600">Exam = Exam Score</span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-600">
                        Final Grade = (Prelim × {{ $finalRatingConfig['prelim_weight'] }}%) + 
                        (Midterm × {{ $finalRatingConfig['midterm_weight'] }}%) + 
                        (Finals × {{ $finalRatingConfig['finals_weight'] }}%)
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center">
                    <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>Passing: ≥ 75</span>
                </div>
                <div class="flex items-center">
                    <span class="text-red-600 font-medium"><i class="fas fa-times-circle mr-1"></i>Failing: < 75</span>
                </div>
            </div>
        </div>
    </div>
@endif

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

.bg-green-25 {
    background-color: #f7fdf9;
}

.bg-purple-25 {
    background-color: #faf8ff;
}

.bg-teal-25 {
    background-color: #f0fdfa;
}
</style>
@endpush
