@extends('layouts.admin')

@section('page-title', 'Full Grade Matrix - ' . $subject->subject_code)

@section('breadcrumbs')
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('subjects.index') }}" class="text-gray-500 hover:text-gray-700">Subjects</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('subjects.show', $subject) }}" class="text-gray-500 hover:text-gray-700">{{ $subject->subject_code }}</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li class="text-gray-900">Full Grade Matrix</li>
@endsection

@section('content')
<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-table mr-2" style="color: #08695A;"></i>
                    Full Grade Matrix
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $subject->subject_code }} - {{ $subject->subject_name }} 
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2" style="background-color: rgba(8, 105, 90, 0.1); color: #08695A;">
                        {{ $subject->section }}
                    </span>
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <a href="{{ route('grades.term', ['subject' => $subject]) }}" 
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #08695A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#08695A';">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Grades
                </a>
                <button @click="$dispatch('open-export-modal')"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #E67E22;"
                   onmouseover="this.style.backgroundColor='#D35400';"
                   onmouseout="this.style.backgroundColor='#E67E22';">
                    <i class="fas fa-file-export mr-2"></i>
                    Export
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
                <i class="fas fa-tasks text-gray-400 mr-2"></i>
                <span class="text-gray-600">Total Activities:</span>
                <span class="font-semibold text-gray-900 ml-1">{{ $allActivities->flatten()->count() }}</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-chart-line text-gray-400 mr-2"></i>
                <span class="text-gray-600">Complete grade breakdown from Prelim to Finals</span>
            </div>
        </div>
    </div>
</div>


<!-- Final Rating Formula Configuration -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{
    expanded: false,
    prelimWeight: {{ $finalRatingConfig['prelim_weight'] ?? 30 }},
    midtermWeight: {{ $finalRatingConfig['midterm_weight'] ?? 30 }},
    finalsWeight: {{ $finalRatingConfig['finals_weight'] ?? 40 }},
    get totalWeight() {
        return parseFloat(this.prelimWeight) + parseFloat(this.midtermWeight) + parseFloat(this.finalsWeight);
    },
    saveFinalRatingWeights() {
        const weights = {
            prelim_weight: parseFloat(this.prelimWeight),
            midterm_weight: parseFloat(this.midtermWeight),
            finals_weight: parseFloat(this.finalsWeight)
        };
        
        if (weights.prelim_weight + weights.midterm_weight + weights.finals_weight !== 100) {
            alert('Weights must total 100%');
            return;
        }
        
        fetch('{{ route('grades.save-final-rating-config', $subject) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(weights)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Final rating weights saved successfully!');
                window.location.reload(true);
            } else {
                alert('Error: ' + (data.message || 'Failed to save weights'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to save weights');
        });
    }
}">
    <!-- Formula Summary Card -->
    <button @click="expanded = !expanded" 
            class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-all">
        <div class="flex items-center space-x-4">
            <i class="fas fa-calculator text-2xl text-orange-600"></i>
            <div class="text-left">
                <div class="text-sm text-gray-600 font-medium">Final Rating Formula (Customizable)</div>
                <div class="text-lg font-semibold text-gray-900">
                    FR = (<span class="text-blue-600" x-text="prelimWeight + '%'"></span> × Prelim) + 
                    (<span class="text-green-600" x-text="midtermWeight + '%'"></span> × Midterm) + 
                    (<span class="text-purple-600" x-text="finalsWeight + '%'"></span> × Finals)
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm text-gray-500">Click to customize</span>
            <i class="fas fa-chevron-down transition-transform text-gray-400" :class="{ 'rotate-180': expanded }"></i>
        </div>
    </button>

    <!-- Formula Configuration -->
    <div x-show="expanded" x-cloak x-transition class="px-6 pb-6 border-t border-gray-200">
        <div class="mt-4 p-4 bg-gradient-to-r from-orange-50 to-yellow-50 rounded-lg border-2 border-orange-300">
            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-sliders-h mr-2 text-orange-600"></i>
                Customize Final Rating Weights
            </h4>
            <form @submit.prevent="saveFinalRatingWeights()" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-play mr-1 text-blue-600"></i>
                            Prelims (%)
                        </label>
                        <input type="number" x-model="prelimWeight" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-center text-lg font-semibold">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-pause mr-1 text-green-600"></i>
                            Midterm (%)
                        </label>
                        <input type="number" x-model="midtermWeight" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-center text-lg font-semibold">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-stop mr-1 text-purple-600"></i>
                            Finals (%)
                        </label>
                        <input type="number" x-model="finalsWeight" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-center text-lg font-semibold">
                    </div>
                    <div class="flex items-end">
                        <div class="w-full text-center p-3 rounded-lg" :class="totalWeight === 100 ? 'bg-green-100 border-2 border-green-500' : 'bg-red-100 border-2 border-red-500'">
                            <div class="text-xs text-gray-600 mb-1">Total</div>
                            <div class="text-2xl font-bold" :class="totalWeight === 100 ? 'text-green-700' : 'text-red-700'" x-text="totalWeight + '%'"></div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end pt-4 border-t border-orange-200">
                    <button type="submit" 
                            :disabled="totalWeight !== 100"
                            :class="totalWeight === 100 ? 'opacity-100 cursor-pointer' : 'opacity-50 cursor-not-allowed'"
                            class="px-6 py-2 text-white rounded-lg font-medium transition-colors"
                            style="background-color: #E67E22;">
                        <i class="fas fa-save mr-2"></i>Save Weights
                    </button>
                </div>
            </form>
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
        
        $terms = ['prelim', 'midterm', 'finals'];
        $termColors = [
            'prelim' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-900', 'border' => 'border-blue-200'],
            'midterm' => ['bg' => 'bg-green-50', 'text' => 'text-green-900', 'border' => 'border-green-200'],
            'finals' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-900', 'border' => 'border-purple-200'],
        ];
    @endphp

    <!-- Full Grade Matrix Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <!-- Student Column -->
                        <th rowspan="2" class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r-2 border-gray-300" style="min-width: 200px;">
                            Student
                        </th>
                        
                        <!-- Term Headers -->
                        @foreach($terms as $term)
                            @php
                                $termActivities = $allActivities->get($term, collect());
                                $activityCount = $termActivities->count();
                                // Columns: Activities + Class Standing + Exam + Term Grade = activityCount + 3
                                $colspan = $activityCount + 3;
                            @endphp
                            <th colspan="{{ $colspan }}" class="px-2 py-2 text-center text-sm font-bold uppercase tracking-wider {{ $termColors[$term]['bg'] }} {{ $termColors[$term]['text'] }} border-l-2 {{ $termColors[$term]['border'] }}">
                                <i class="fas fa-{{ $term === 'prelim' ? 'play' : ($term === 'midterm' ? 'pause' : 'stop') }} mr-1"></i>
                                {{ ucfirst($term) }}
                            </th>
                        @endforeach
                        
                        <!-- Final Rating Header -->
                        <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider bg-teal-100 text-teal-900 border-l-2 border-teal-300" style="min-width: 100px;">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-trophy mb-1"></i>
                                <span>Final</span>
                                <span>Rating</span>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <!-- Sub-headers for each term -->
                        @foreach($terms as $term)
                            @php
                                $termActivities = $allActivities->get($term, collect());
                            @endphp
                            
                            <!-- Activity columns -->
                            @forelse($termActivities as $activity)
                                <th class="px-2 py-2 text-center text-xs font-medium text-gray-600 {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}" style="min-width: 60px;">
                                    <div class="truncate" title="{{ $activity->name }}">
                                        {{ Str::limit($activity->name, 10) }}
                                    </div>
                                    <div class="text-xs text-gray-400">({{ $activity->max_score }})</div>
                                </th>
                            @empty
                                <th class="px-2 py-2 text-center text-xs font-medium text-gray-400 {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}">
                                    No Activities
                                </th>
                            @endforelse
                            
                            <!-- Class Standing -->
                            <th class="px-2 py-2 text-center text-xs font-medium {{ $termColors[$term]['text'] }} {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}" style="min-width: 70px;">
                                <div>CS</div>
                                <div class="text-xs text-gray-400">(40%)</div>
                            </th>
                            
                            <!-- Exam -->
                            <th class="px-2 py-2 text-center text-xs font-medium {{ $termColors[$term]['text'] }} {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}" style="min-width: 70px;">
                                <div>Exam</div>
                                <div class="text-xs text-gray-400">(60%)</div>
                            </th>
                            
                            <!-- Term Grade -->
                            <th class="px-2 py-2 text-center text-xs font-bold {{ $termColors[$term]['text'] }} {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}" style="min-width: 80px;">
                                <div>Term</div>
                                <div>Grade</div>
                            </th>
                        @endforeach
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
                                <div class="flex items-center space-x-2">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold" 
                                             style="background: linear-gradient(135deg, #08695A, #0A7B6A);">
                                            {{ substr($studentMapping->formatted_name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $studentMapping->formatted_name }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Term Data -->
                            @foreach($terms as $term)
                                @php
                                    $termActivities = $allActivities->get($term, collect());
                                    $termGrade = $studentTermGrades->where('term', $term)->first();
                                @endphp
                                
                                <!-- Activity Scores -->
                                @forelse($termActivities as $activity)
                                    @php
                                        $gradeRecord = $gradeMatrix[$studentMapping->id][$activity->id] ?? null;
                                        $score = $gradeRecord?->score;
                                    @endphp
                                    <td class="px-2 py-2 text-center text-sm {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}">
                                        @if($score !== null)
                                            <span class="font-medium {{ $score >= ($activity->max_score * 0.75) ? 'text-green-700' : ($score >= ($activity->max_score * 0.5) ? 'text-yellow-700' : 'text-red-700') }}">
                                                {{ number_format($score, 0) }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @empty
                                    <td class="px-2 py-2 text-center text-sm text-gray-300 {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}">—</td>
                                @endforelse
                                
                                <!-- Class Standing -->
                                <td class="px-2 py-2 text-center text-sm font-medium {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}">
                                    @if($termGrade && $termGrade->class_standing !== null)
                                        <span class="{{ $termColors[$term]['text'] }}">{{ number_format($termGrade->class_standing, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <!-- Exam Score -->
                                <td class="px-2 py-2 text-center text-sm font-medium {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}">
                                    @if($termGrade && $termGrade->exam_grade !== null)
                                        <span class="{{ $termColors[$term]['text'] }}">{{ number_format($termGrade->exam_grade, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <!-- Term Grade -->
                                <td class="px-2 py-2 text-center {{ $termColors[$term]['bg'] }} border-l {{ $termColors[$term]['border'] }}">
                                    @if($termGrade && $termGrade->term_grade !== null)
                                        <span class="text-lg font-bold {{ $termColors[$term]['text'] }}">
                                            {{ number_format($termGrade->term_grade, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            
                            <!-- Final Rating -->
                            <td class="px-4 py-2 text-center bg-teal-50 border-l-2 border-teal-300">
                                @if($finalGrade !== null)
                                    <div class="space-y-1">
                                        <div class="text-xl font-bold text-teal-900">
                                            {{ number_format($finalGrade, 2) }}
                                        </div>
                                        <div class="text-xs font-medium {{ $finalGrade >= 75 ? 'text-green-600' : 'text-red-600' }}">
                                            @if($finalGrade >= 75)
                                                <i class="fas fa-check-circle"></i> PASS
                                            @else
                                                <i class="fas fa-times-circle"></i> FAIL
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-300">—</span>
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
                    <span class="text-gray-600"><strong>CS</strong> = Class Standing (Weighted)</span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-600"><strong>Exam</strong> = Exam Grade (Weighted)</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center">
                    <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>Pass: ≥ 75</span>
                </div>
                <div class="flex items-center">
                    <span class="text-red-600 font-medium"><i class="fas fa-times-circle mr-1"></i>Fail: < 75</span>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Export Modal -->
<div x-data="{ 
    open: false,
    deanName: '',
    proceedExport() {
        if (!this.deanName.trim()) {
            alert('Please enter the Dean\'s name');
            return;
        }
        
        // Trigger download
        window.location.href = '{{ route('grades.export.full-matrix', $subject) }}?dean_name=' + encodeURIComponent(this.deanName);
        
        // Close modal
        this.open = false;
        this.deanName = '';
    }
}" 
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

        <!-- Centering trick -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div x-show="open" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
             style="position: relative; z-index: 10;">
            
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-file-pdf text-orange-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Export Full Grade Matrix
                        </h3>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 mb-4">
                                Export the complete grade matrix for <strong>{{ $subject->subject_code }}</strong> to PDF.
                            </p>
                            
                            <!-- Export Info -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                    PDF will include:
                                </h4>
                                <ul class="text-sm text-gray-600 space-y-1 ml-5 list-disc">
                                    <li>All student names</li>
                                    <li>Activity scores for Prelim, Midterm, and Finals</li>
                                    <li>Class Standing and Exam grades per term</li>
                                    <li>Term grades and Final Rating</li>
                                    <li>Pass/Fail remarks</li>
                                </ul>
                            </div>
                            
                            <!-- Dean Name Input -->
                            <div class="mt-4">
                                <label for="dean_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user-tie mr-1"></i>
                                    Dean's Name (for signature)
                                </label>
                                <input type="text" 
                                       id="dean_name" 
                                       x-model="deanName"
                                       placeholder="Enter Dean's name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        @click="proceedExport()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm"
                        style="background-color: #E67E22;"
                        onmouseover="this.style.backgroundColor='#D35400';"
                        onmouseout="this.style.backgroundColor='#E67E22';">
                    <i class="fas fa-download mr-2"></i>
                    Export to PDF
                </button>
                <button type="button" 
                        @click="open = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
