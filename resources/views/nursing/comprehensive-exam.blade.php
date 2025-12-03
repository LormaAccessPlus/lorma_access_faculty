@extends('layouts.admin')

@section('title', 'Comprehensive Exam - ' . $subject->subject_code)

@section('page-title', 'Comprehensive Exam Scores')

@section('breadcrumbs')
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('grade-matrix.nursing') }}" class="text-gray-500 hover:text-gray-700">Nursing Matrix</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('grade-matrix.nursing.matrix', $subject) }}" class="text-gray-500 hover:text-gray-700">{{ $subject->subject_code }}</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li class="text-gray-900">Comprehensive Exam</li>
@endsection

@section('content')
<div x-data="comprehensiveExamManager()" class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-graduation-cap mr-3 text-orange-600"></i>
                    Comprehensive Exam Scores
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $subject->subject_code }} - {{ $subject->subject_name }}
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2" style="background-color: rgba(8, 105, 90, 0.1); color: #08695A;">
                        {{ $subject->section }}
                    </span>
                </p>
            </div>
            <div class="flex gap-3">
                <button @click="importModal = true" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-file-import mr-2"></i>
                    Import CSV
                </button>
                <button @click="saveAllScores()" 
                        :disabled="!hasChanges"
                        :class="hasChanges ? 'opacity-100' : 'opacity-50 cursor-not-allowed'"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white transition-colors"
                        style="background-color: #08695A;"
                        onmouseover="if(!this.disabled) this.style.backgroundColor='#065A4A';"
                        onmouseout="this.style.backgroundColor='#08695A';">
                    <i class="fas fa-save mr-2"></i>
                    Save All Scores
                </button>
                <a href="{{ route('grade-matrix.nursing.matrix', $subject) }}" 
                   class="inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md bg-white transition-colors"
                   style="border-color: #08695A; color: #08695A;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Matrix
                </a>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-orange-600 mt-1 mr-3"></i>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-orange-900 mb-1">About Comprehensive Exam</h3>
                <p class="text-sm text-orange-800">
                    The comprehensive exam score contributes <strong>20%</strong> to the final rating. 
                    Formula: <code class="bg-orange-100 px-2 py-1 rounded">Final Rating = (Final Grade × 80%) + (Comprehensive Exam × 20%)</code>
                </p>
            </div>
        </div>
    </div>

    <!-- Scores Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Student Scores</h2>
                <div class="text-sm text-gray-600">
                    <span x-text="completedCount"></span> of {{ $subject->studentMappings->count() }} students completed
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Student
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Score (0-100)
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Transmuted Score
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($subject->studentMappings as $index => $studentMapping)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-white text-sm font-semibold" 
                                             style="background: linear-gradient(135deg, #08695A, #0A7B6A);">
                                            {{ substr($studentMapping->student_name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $studentMapping->student_name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $studentMapping->student_email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            @php
                                $studentId = $studentMapping->id;
                                $currentScore = isset($comprehensiveExamScores[$studentId]) ? $comprehensiveExamScores[$studentId] : null;
                            @endphp
                            <td class="px-6 py-4 text-center">
                                <input type="number" 
                                       x-model="scores[{{ $studentId }}]"
                                       @input="checkChanges()"
                                       min="0" 
                                       max="100" 
                                       step="0.01"
                                       placeholder="Enter score"
                                       class="w-32 px-3 py-2 border border-gray-300 rounded-md text-center focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span x-show="scores[{{ $studentId }}] && scores[{{ $studentId }}] !== ''" 
                                      class="text-lg font-semibold"
                                      :class="((parseFloat(scores[{{ $studentId }}]) / 100) * 60 + 40) >= 75 ? 'text-green-600' : 'text-red-600'"
                                      x-text="scores[{{ $studentId }}] ? (((parseFloat(scores[{{ $studentId }}]) / 100) * 60 + 40).toFixed(2)) : ''"></span>
                                <span x-show="!scores[{{ $studentId }}] || scores[{{ $studentId }}] === ''" class="text-gray-400">—</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span x-show="scores[{{ $studentId }}] && scores[{{ $studentId }}] !== ''">
                                    <span x-show="((parseFloat(scores[{{ $studentId }}]) / 100) * 60 + 40) >= 75" 
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Passing
                                    </span>
                                    <span x-show="((parseFloat(scores[{{ $studentId }}]) / 100) * 60 + 40) < 75" 
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>Failing
                                    </span>
                                </span>
                                <span x-show="!scores[{{ $studentId }}] || scores[{{ $studentId }}] === ''" class="text-gray-400">—</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="clearScore({{ $studentId }})" 
                                        x-show="scores[{{ $studentId }}] && scores[{{ $studentId }}] !== ''"
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="importModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="importModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 @click="importModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="importModal" 
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
                            <i class="fas fa-file-import text-orange-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Import Comprehensive Exam Scores
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Upload a CSV file with student emails and scores. Format: <code class="bg-gray-100 px-2 py-1 rounded">email,score</code>
                                </p>
                                <input type="file" 
                                       @change="handleFileUpload($event)"
                                       accept=".csv"
                                       class="block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-md file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-orange-50 file:text-orange-700
                                              hover:file:bg-orange-100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            @click="importModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function comprehensiveExamManager() {
    return {
        scores: {},
        originalScores: {},
        hasChanges: false,
        importModal: false,
        
        init() {
            // Initialize scores as an object with student IDs as keys
            const rawScores = @json($comprehensiveExamScores);
            console.log('Raw scores from backend:', rawScores);
            
            // Convert to object if it's an array, or use as-is if already an object
            if (Array.isArray(rawScores)) {
                // If it's an array, we need to map it properly
                @foreach($subject->studentMappings as $index => $sm)
                    this.scores[{{ $sm->id }}] = rawScores[{{ $index }}] !== undefined ? rawScores[{{ $index }}] : null;
                @endforeach
            } else {
                // If it's already an object, use it directly
                this.scores = rawScores || {};
            }
            
            // Ensure all student IDs exist
            @foreach($subject->studentMappings as $sm)
                if (this.scores[{{ $sm->id }}] === undefined) {
                    this.scores[{{ $sm->id }}] = null;
                }
            @endforeach
            
            this.originalScores = JSON.parse(JSON.stringify(this.scores));
            console.log('Initialized scores:', this.scores);
        },
        
        get completedCount() {
            return Object.values(this.scores).filter(score => score !== null && score !== '').length;
        },
        
        updateScore(studentId, value) {
            this.scores[studentId] = value === '' ? null : parseFloat(value);
            this.checkChanges();
        },
        
        clearScore(studentId) {
            this.scores[studentId] = null;
            this.checkChanges();
        },
        
        checkChanges() {
            this.hasChanges = JSON.stringify(this.scores) !== JSON.stringify(this.originalScores);
        },
        
        async saveAllScores() {
            try {
                const response = await fetch('{{ route("nursing.comprehensive-exam.store", $subject) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ scores: this.scores })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Comprehensive exam scores saved successfully!');
                    this.originalScores = { ...this.scores };
                    this.hasChanges = false;
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save scores');
            }
        },
        
        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const response = await fetch('{{ route("nursing.comprehensive-exam.import", $subject) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`Successfully imported ${data.imported_count} scores!`);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to import scores');
            }
            
            this.importModal = false;
        }
    };
}
</script>
@endsection
