@extends('layouts.admin')

@section('page-title', 'Archived: ' . $subject->subject_code)

@section('breadcrumbs')
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('archive.index') }}" class="text-gray-500 hover:text-gray-700">Archived Subjects</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li class="text-gray-900">{{ $subject->subject_code }}</li>
@endsection

@section('content')
<!-- Archived Notice Banner -->
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

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $subject->subject_code }}</h1>
                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-archive mr-1"></i>
                        Archived
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $subject->subject_name }} 
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2" style="background-color: rgba(8, 105, 90, 0.1); color: #08695A;">
                        {{ $subject->section }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ $subject->academic_year }} - Semester {{ $subject->semester }}
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <button onclick="exportGrades()"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #E67E22;"
                   onmouseover="this.style.backgroundColor='#D35400';"
                   onmouseout="this.style.backgroundColor='#E67E22';">
                    <i class="fas fa-file-export mr-2"></i>
                    Export Grades
                </button>
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
    
    <!-- Stats Bar -->
    <div class="px-6 py-3 bg-gray-50">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <div class="flex items-center">
                <i class="fas fa-users text-gray-400 mr-2"></i>
                <span class="text-gray-600">Students:</span>
                <span class="font-semibold text-gray-900 ml-1">{{ $subject->studentMappings->count() }}</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-tasks text-gray-400 mr-2"></i>
                <span class="text-gray-600">Activities:</span>
                <span class="font-semibold text-gray-900 ml-1">{{ $allActivities->flatten()->count() }}</span>
            </div>
        </div>
    </div>
</div>


<!-- Final Rating Formula Info -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 p-4">
    <div class="flex items-center space-x-4">
        <i class="fas fa-calculator text-xl text-orange-600"></i>
        <div>
            <div class="text-sm text-gray-600 font-medium">Final Rating Formula</div>
            <div class="text-lg font-semibold text-gray-900">
                FR = (<span class="text-blue-600">{{ $finalRatingConfig['prelim_weight'] }}%</span> × Prelim) + 
                (<span class="text-green-600">{{ $finalRatingConfig['midterm_weight'] }}%</span> × Midterm) + 
                (<span class="text-purple-600">{{ $finalRatingConfig['finals_weight'] }}%</span> × Finals)
            </div>
        </div>
    </div>
</div>

<!-- Grade Matrix -->
@if($subject->studentMappings->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i class="fas fa-users text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Students</h3>
            <p class="text-gray-500">No students were mapped to this subject before it was archived.</p>
        </div>
    </div>
@else
    @php
        $terms = ['prelim', 'midterm', 'finals'];
        $termColors = [
            'prelim' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-900', 'border' => 'border-blue-200'],
            'midterm' => ['bg' => 'bg-green-50', 'text' => 'text-green-900', 'border' => 'border-green-200'],
            'finals' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-900', 'border' => 'border-purple-200'],
        ];
        
        // Calculate final grades
        $finalGrades = [];
        foreach($subject->studentMappings as $studentMapping) {
            $studentTermGrades = $termGrades->get($studentMapping->id, collect());
            $prelim = $studentTermGrades->where('term', 'prelim')->first()?->term_grade;
            $midterm = $studentTermGrades->where('term', 'midterm')->first()?->term_grade;
            $finals = $studentTermGrades->where('term', 'finals')->first()?->term_grade;
            
            if ($prelim !== null && $midterm !== null && $finals !== null) {
                $finalRating = ($prelim * ($finalRatingConfig['prelim_weight'] / 100)) +
                              ($midterm * ($finalRatingConfig['midterm_weight'] / 100)) +
                              ($finals * ($finalRatingConfig['finals_weight'] / 100));
                $finalGrades[$studentMapping->id] = round($finalRating, 2);
            } else {
                $finalGrades[$studentMapping->id] = null;
            }
        }
    @endphp

    <!-- Grade Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r-2 border-gray-300" style="min-width: 200px;">
                            Student
                        </th>
                        @foreach($terms as $term)
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider {{ $termColors[$term]['bg'] }} {{ $termColors[$term]['text'] }} border-l {{ $termColors[$term]['border'] }}">
                                <div class="flex flex-col items-center">
                                    <span class="font-bold">{{ ucfirst($term) }}</span>
                                    <span class="text-xs font-normal opacity-75">Term Grade</span>
                                </div>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider bg-teal-100 text-teal-900 border-l-2 border-teal-300">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-trophy mb-1"></i>
                                <span>Final Rating</span>
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider bg-gray-100 text-gray-700 border-l border-gray-300">
                            Remarks
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($subject->studentMappings as $index => $studentMapping)
                        @php
                            $studentTermGrades = $termGrades->get($studentMapping->id, collect());
                            $finalGrade = $finalGrades[$studentMapping->id];
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-25' }}">
                            <!-- Student Info -->
                            <td class="sticky left-0 z-10 bg-inherit px-4 py-3 border-r-2 border-gray-300">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold" 
                                             style="background: linear-gradient(135deg, #08695A, #0A7B6A);">
                                            {{ substr($studentMapping->student_name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $studentMapping->student_name }}</p>
                                        @if($studentMapping->student_email)
                                            <p class="text-xs text-gray-500">{{ $studentMapping->student_email }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Term Grades -->
                            @foreach($terms as $term)
                                @php
                                    $termGrade = $studentTermGrades->where('term', $term)->first();
                                @endphp
                                <td class="px-4 py-3 text-center {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}">
                                    @if($termGrade && $termGrade->term_grade !== null)
                                        <div class="space-y-1">
                                            <div class="text-lg font-bold {{ $termColors[$term]['text'] }}">
                                                {{ number_format($termGrade->term_grade, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                CS: {{ number_format($termGrade->class_standing ?? 0, 1) }} | 
                                                Ex: {{ number_format($termGrade->exam_grade ?? 0, 1) }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            
                            <!-- Final Rating -->
                            <td class="px-4 py-3 text-center bg-teal-50 border-l-2 border-teal-300">
                                @if($finalGrade !== null)
                                    <span class="text-xl font-bold text-teal-900">{{ number_format($finalGrade, 2) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            
                            <!-- Remarks -->
                            <td class="px-4 py-3 text-center border-l border-gray-300">
                                @if($finalGrade !== null)
                                    @if($finalGrade >= 75)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>PASSED
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i>FAILED
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-xs">Incomplete</span>
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
        <div class="flex items-center justify-between flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-4">
                <span class="text-gray-600"><strong>CS</strong> = Class Standing</span>
                <span class="text-gray-600"><strong>Ex</strong> = Exam Grade</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>Pass: ≥ 75</span>
                <span class="text-red-600 font-medium"><i class="fas fa-times-circle mr-1"></i>Fail: < 75</span>
            </div>
        </div>
    </div>
@endif

<!-- Export Script -->
<script>
function exportGrades() {
    const deanName = prompt('Enter Dean\'s name for the PDF signature:', '');
    if (deanName !== null) {
        window.location.href = '{{ route('grades.export.full-matrix', $subject) }}?dean_name=' + encodeURIComponent(deanName);
    }
}
</script>
@endsection
