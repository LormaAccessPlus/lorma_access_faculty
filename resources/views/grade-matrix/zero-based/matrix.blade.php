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
                <a href="{{ route('grade-matrix.zero-based.term', $subject) }}" 
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
                <a href="{{ route('grade-matrix.zero-based') }}" 
                   class="inline-flex items-center px-3 py-2 border text-sm leading-4 font-medium rounded-md bg-white transition-colors"
                   style="border-color: #08695A; color: #08695A;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Zero-based Matrix
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

<!-- Final Rating Weights Configuration -->
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
                alert('Final rating weights saved successfully! Grades will be recalculated.');
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
            <i class="fas fa-sliders-h text-2xl text-orange-600"></i>
            <div class="text-left">
                <div class="text-sm text-gray-600 font-medium">Final Rating Formula</div>
                <div class="text-lg font-semibold text-gray-900">
                    <span class="text-blue-600" x-text="prelimWeight + '%'"></span> Prelims + 
                    <span class="text-green-600" x-text="midtermWeight + '%'"></span> Midterm + 
                    <span class="text-purple-600" x-text="finalsWeight + '%'"></span> Finals
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm text-gray-500">Click to edit weights</span>
            <i class="fas fa-chevron-down transition-transform text-gray-400" :class="{ 'rotate-180': expanded }"></i>
        </div>
    </button>

    <!-- Formula Configuration -->
    <div x-show="expanded" x-cloak x-transition class="px-6 pb-6 border-t border-gray-200">
        <div class="mt-4 p-4 bg-gradient-to-r from-orange-50 to-yellow-50 rounded-lg border-2 border-orange-300">
            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-trophy mr-2 text-orange-600"></i>
                Final Rating Weights (Editable)
            </h4>
            <form @submit.prevent="saveFinalRatingWeights()" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-play mr-1 text-blue-600"></i>
                            Prelims Weight (%)
                        </label>
                        <input type="number" x-model="prelimWeight" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-center text-lg font-semibold">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-pause mr-1 text-green-600"></i>
                            Midterm Weight (%)
                        </label>
                        <input type="number" x-model="midtermWeight" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-center text-lg font-semibold">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-stop mr-1 text-purple-600"></i>
                            Finals Weight (%)
                        </label>
                        <input type="number" x-model="finalsWeight" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border-2 border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 text-center text-lg font-semibold">
                    </div>
                    <div class="flex items-end">
                        <div class="w-full text-center p-3 rounded-lg" :class="totalWeight === 100 ? 'bg-green-100 border-2 border-green-500' : 'bg-red-100 border-2 border-red-500'">
                            <div class="text-xs text-gray-600 mb-1">Total</div>
                            <div class="text-2xl font-bold" :class="totalWeight === 100 ? 'text-green-700' : 'text-red-700'" x-text="totalWeight + '%'"></div>
                            <div class="text-xs mt-1" :class="totalWeight === 100 ? 'text-green-600' : 'text-red-600'">
                                <i :class="totalWeight === 100 ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-4 border border-orange-200">
                    <div class="text-center mb-3">
                        <div class="text-sm text-gray-600 mb-1">Final Rating Formula</div>
                        <div class="text-lg font-bold text-orange-700">
                            FR = (Prelims × <span x-text="prelimWeight"></span>%) + (Midterm × <span x-text="midtermWeight"></span>%) + (Finals × <span x-text="finalsWeight"></span>%)
                        </div>
                    </div>
                    <div class="text-xs text-gray-600 text-center">
                        <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                        Example: If Prelims=85, Midterm=90, Finals=88 → FR = (85×<span x-text="prelimWeight"></span>% + 90×<span x-text="midtermWeight"></span>% + 88×<span x-text="finalsWeight"></span>%)
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-4 border-t border-orange-200">
                    <p class="text-xs text-gray-600" x-show="totalWeight !== 100">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-1"></i>
                        <span class="text-red-600 font-semibold">Warning:</span> Weights must total 100% for accurate calculation
                    </p>
                    <button type="submit" 
                            :disabled="totalWeight !== 100"
                            :class="totalWeight === 100 ? 'opacity-100 cursor-pointer' : 'opacity-50 cursor-not-allowed'"
                            class="px-6 py-2 text-white rounded-lg font-medium transition-colors"
                            style="background-color: #E67E22;"
                            onmouseover="if(this.disabled === false) this.style.backgroundColor='#D35400';"
                            onmouseout="this.style.backgroundColor='#E67E22';">
                        <i class="fas fa-save mr-2"></i>Save Final Rating Weights
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
        // NOTE: $termGrades is passed from controller with dynamically calculated values
        // Do NOT re-query the database here as it will override the dynamic calculations
        
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
                                            {{ number_format($prelimGrade->term_grade, 2) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            CS: {{ number_format($prelimGrade->class_standing, 2) }} | 
                                            Exam: {{ number_format($prelimGrade->exam_score ?? 0, 0) }}
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
                                            {{ number_format($midtermGrade->term_grade, 2) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            CS: {{ number_format($midtermGrade->class_standing, 2) }} | 
                                            Exam: {{ number_format($midtermGrade->exam_score ?? 0, 0) }}
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
                                            {{ number_format($finalsGrade->term_grade, 2) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            CS: {{ number_format($finalsGrade->class_standing, 2) }} | 
                                            Exam: {{ number_format($finalsGrade->exam_score ?? 0, 0) }}
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
                                            {{ number_format($finalGrade, 2) }}
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
<div x-data="{ 
    open: false,
    deanName: '',
    exportType: '',
    exportTerm: '',
    showDeanInput: false,
    showNotification: false,
    notificationMessage: '',
    promptForDean(type, term = '') {
        this.exportType = type;
        this.exportTerm = term;
        this.showDeanInput = true;
        this.deanName = '';
    },
    proceedExport() {
        if (!this.deanName.trim()) {
            alert('Please enter the Dean\'s name');
            return;
        }
        let url = '';
        let exportName = '';
        if (this.exportType === 'pp') {
            url = '{{ route('grades.export.pp', $subject) }}';
            exportName = 'Final Grade';
        } else if (this.exportType === 'term') {
            url = '{{ route('grades.export.term', ['subject' => $subject, 'term' => '__TERM__']) }}'.replace('__TERM__', this.exportTerm);
            exportName = this.exportTerm.charAt(0).toUpperCase() + this.exportTerm.slice(1) + ' Term';
        }
        
        // Trigger download
        window.location.href = url + '?dean_name=' + encodeURIComponent(this.deanName);
        
        // Close modal and show notification
        this.open = false;
        this.showDeanInput = false;
        this.notificationMessage = exportName + ' exported successfully!';
        this.showNotification = true;
        
        // Auto-hide notification after 30 seconds
        setTimeout(() => {
            this.showNotification = false;
        }, 30000);
    }
}" 
     @open-export-modal.window="open = true; showDeanInput = false;"
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
             @click="open = false; showDeanInput = false;"></div>

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
            
            <!-- Export Options View -->
            <div x-show="!showDeanInput">
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
                                    <!-- Export Final Grade -->
                                    <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer"
                                         @click="promptForDean('pp')">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-trophy text-green-500 text-xl"></i>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h4 class="text-sm font-medium text-gray-900">Final Grade</h4>
                                                <p class="text-xs text-gray-500 mt-1">Export computed final grade</p>
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
                                            <div @click="promptForDean('term', 'prelim')"
                                               class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors cursor-pointer">
                                                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                                Prelim Term
                                            </div>
                                            <div @click="promptForDean('term', 'midterm')"
                                               class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors cursor-pointer">
                                                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                                Midterm Term
                                            </div>
                                            <div @click="promptForDean('term', 'finals')"
                                               class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors cursor-pointer">
                                                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                                Finals Term
                                            </div>
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

            <!-- Dean Name Input View -->
            <div x-show="showDeanInput">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-user-tie text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Enter Dean's Name
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Please enter the name of the Dean who will note this document.
                                </p>
                                <input type="text" 
                                       x-model="deanName"
                                       @keydown.enter="proceedExport()"
                                       placeholder="Dean's Full Name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            @click="proceedExport()"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            style="background-color: #08695A;"
                            onmouseover="this.style.backgroundColor='#065A4A';"
                            onmouseout="this.style.backgroundColor='#08695A';">
                        Export PDF
                    </button>
                    <button type="button" 
                            @click="showDeanInput = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Back
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Notification -->
    <div x-show="showNotification"
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
         style="display: none;">
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900">
                        Export Successful
                    </p>
                    <p class="mt-1 text-sm text-gray-500" x-text="notificationMessage"></p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="showNotification = false" 
                            class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
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
