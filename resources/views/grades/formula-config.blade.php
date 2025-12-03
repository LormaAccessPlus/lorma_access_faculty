@extends('layouts.admin')

@section('title', 'Formula Configuration - ' . $subject->subject_code)

@section('content')
<div class="container mx-auto px-4 py-6" x-data="formulaConfig()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-calculator mr-2" style="color: #08695A;"></i>
                    Excel-like Formula Configuration
                </h1>
                <p class="text-gray-600 mt-1">
                    {{ $subject->subject_code }} - {{ $subject->subject_name }} ({{ ucfirst($term) }} Term)
                </p>
            </div>
            <a href="{{ route('grade-matrix.customized.term', ['subject' => $subject, 'term' => $term]) }}" 
               class="px-4 py-2 rounded-lg font-medium transition-colors border"
               style="border-color: #08695A; color: #08695A; background-color: white;"
               onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
               onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                <i class="fas fa-arrow-left mr-2"></i>Back to Grades
            </a>
        </div>
    </div>

    <!-- Help Section -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6" x-data="{ expanded: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
            <h3 class="text-sm font-semibold text-blue-900">
                <i class="fas fa-info-circle mr-2"></i>How to Use Formulas
            </h3>
            <i class="fas fa-chevron-down transition-transform text-blue-900" :class="{ 'rotate-180': expanded }"></i>
        </div>
        <div x-show="expanded" x-cloak x-transition class="mt-3 text-sm text-blue-800 space-y-2">
            <div>
                <p class="font-semibold">Variables:</p>
                <p>Use A1, A2, A3... for activities, Q1, Q2... for quizzes, E1 for exam, CS for class standing, EXAM for exam score</p>
            </div>
            <div>
                <p class="font-semibold">Operators:</p>
                <p>+ (add), - (subtract), * (multiply), / (divide), () (parentheses)</p>
            </div>
            <div>
                <p class="font-semibold">Functions:</p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li><code class="bg-white px-1 rounded">SUM(A1:A5)</code> - Sum of activities 1 to 5</li>
                    <li><code class="bg-white px-1 rounded">AVG(A1,A2,A3)</code> - Average of activities</li>
                    <li><code class="bg-white px-1 rounded">MAX(A1:A5)</code> - Maximum value</li>
                    <li><code class="bg-white px-1 rounded">MIN(A1:A5)</code> - Minimum value</li>
                    <li><code class="bg-white px-1 rounded">IF(A1 > 75, A1, 0)</code> - Conditional (if A1 > 75, use A1, else 0)</li>
                </ul>
            </div>
            <div>
                <p class="font-semibold">Common Examples:</p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li><code class="bg-white px-1 rounded">AVG(A1:A5) * 0.4 + E1 * 0.6</code> - 40% activities, 60% exam</li>
                    <li><code class="bg-white px-1 rounded">(E1 / MAX_SCORE) * 100</code> - Percentage formula</li>
                    <li><code class="bg-white px-1 rounded">(E1 / MAX_SCORE) * 50 + 50</code> - Transmuted formula (50-100)</li>
                    <li><code class="bg-white px-1 rounded">CS * 0.6 + EXAM * 0.4</code> - 60% CS, 40% Exam</li>
                    <li><code class="bg-white px-1 rounded">PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4</code> - Final grade</li>
                </ul>
            </div>
            <div class="pt-2 border-t border-blue-300">
                <a href="{{ asset('CUSTOMIZED_MATRIX_FORMULA_GUIDE.md') }}" target="_blank" class="text-blue-900 hover:underline font-semibold">
                    <i class="fas fa-book mr-1"></i>View Complete Formula Guide
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Formula Templates -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-purple-900 mb-3">
            <i class="fas fa-magic mr-2"></i>Quick Formula Templates
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button type="button" @click="termGradeFormula = 'CS * 0.6 + EXAM * 0.4'" 
                    class="text-left p-3 bg-white rounded-lg hover:shadow-md transition-shadow border border-purple-200">
                <p class="text-xs font-semibold text-purple-900">Standard 60/40</p>
                <code class="text-xs text-gray-700">CS * 0.6 + EXAM * 0.4</code>
            </button>
            <button type="button" @click="termGradeFormula = 'CS * 0.7 + EXAM * 0.3'" 
                    class="text-left p-3 bg-white rounded-lg hover:shadow-md transition-shadow border border-purple-200">
                <p class="text-xs font-semibold text-purple-900">70/30 Split</p>
                <code class="text-xs text-gray-700">CS * 0.7 + EXAM * 0.3</code>
            </button>
            <button type="button" @click="examFormula = '(E1 / MAX_SCORE) * 100'" 
                    class="text-left p-3 bg-white rounded-lg hover:shadow-md transition-shadow border border-purple-200">
                <p class="text-xs font-semibold text-purple-900">Percentage (0-100)</p>
                <code class="text-xs text-gray-700">(E1 / MAX_SCORE) * 100</code>
            </button>
            <button type="button" @click="examFormula = '(E1 / MAX_SCORE) * 50 + 50'" 
                    class="text-left p-3 bg-white rounded-lg hover:shadow-md transition-shadow border border-purple-200">
                <p class="text-xs font-semibold text-purple-900">Transmuted (50-100)</p>
                <code class="text-xs text-gray-700">(E1 / MAX_SCORE) * 50 + 50</code>
            </button>
            <button type="button" @click="quizFormula = 'AVG(Q1:Q5)'" 
                    class="text-left p-3 bg-white rounded-lg hover:shadow-md transition-shadow border border-purple-200">
                <p class="text-xs font-semibold text-purple-900">Quiz Average</p>
                <code class="text-xs text-gray-700">AVG(Q1:Q5)</code>
            </button>
            <button type="button" @click="finalGradeFormula = 'PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4'" 
                    class="text-left p-3 bg-white rounded-lg hover:shadow-md transition-shadow border border-purple-200">
                <p class="text-xs font-semibold text-purple-900">Final 30/30/40</p>
                <code class="text-xs text-gray-700">PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4</code>
            </button>
        </div>
    </div>

    <form @submit.prevent="saveFormulas">
        <!-- Activities Formula Section -->
        @if($activities->where('type', 'lecture')->isNotEmpty() || $activities->where('type', 'lab')->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-tasks mr-2 text-blue-600"></i>
                Activity Formulas
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Define how each activity score is calculated. Leave blank to use raw score.
            </p>

            <div class="space-y-4">
                @foreach($activities as $index => $activity)
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0 w-16 text-center">
                        <span class="inline-block px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded">
                            A{{ $index + 1 }}
                        </span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $activity->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($activity->type) }} - Max Score: {{ $activity->max_score }}</p>
                    </div>
                    <div class="flex-1">
                        <input type="text" 
                               x-model="activityFormulas[{{ $activity->id }}]"
                               placeholder="e.g., (score / {{ $activity->max_score }}) * 100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-mono">
                    </div>
                    <button type="button" 
                            @click="testFormula(activityFormulas[{{ $activity->id }}], { score: {{ $activity->max_score }} })"
                            class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm">
                        <i class="fas fa-flask"></i> Test
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Quiz Formula Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-question-circle mr-2 text-green-600"></i>
                Quiz Formula
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Define how quiz scores are combined. Use Q1, Q2, Q3... for individual quizzes.
            </p>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="text" 
                           x-model="quizFormula"
                           placeholder="e.g., AVG(Q1:Q5) or SUM(Q1,Q2,Q3) / 3"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 font-mono">
                </div>
                <button type="button" 
                        @click="testFormula(quizFormula, { Q1: 85, Q2: 90, Q3: 88, Q4: 92, Q5: 87 })"
                        class="px-4 py-3 bg-green-100 hover:bg-green-200 rounded-lg text-sm font-medium">
                    <i class="fas fa-flask mr-2"></i>Test with Sample
                </button>
            </div>
        </div>

        <!-- Exam Formula Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                Exam Formula
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Define how exam score is calculated. Use E1 for exam score, MAX_SCORE for maximum possible score.
            </p>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="text" 
                           x-model="examFormula"
                           placeholder="e.g., (E1 / MAX_SCORE) * 100 or (E1 / MAX_SCORE) * 50 + 50"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 font-mono">
                </div>
                <button type="button" 
                        @click="testFormula(examFormula, { E1: 45, MAX_SCORE: 50 })"
                        class="px-4 py-3 bg-purple-100 hover:bg-purple-200 rounded-lg text-sm font-medium">
                    <i class="fas fa-flask mr-2"></i>Test with Sample
                </button>
            </div>
        </div>

        <!-- Term Grade Formula Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-calculator mr-2 text-orange-600"></i>
                Term Grade Formula <span class="text-red-600">*</span>
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Define how the term grade is calculated. Use CS for class standing, EXAM for exam score.
            </p>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="text" 
                           x-model="termGradeFormula"
                           placeholder="e.g., CS * 0.6 + EXAM * 0.4"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 font-mono">
                </div>
                <button type="button" 
                        @click="testFormula(termGradeFormula, { CS: 85, EXAM: 90 })"
                        class="px-4 py-3 bg-orange-100 hover:bg-orange-200 rounded-lg text-sm font-medium">
                    <i class="fas fa-flask mr-2"></i>Test with Sample
                </button>
            </div>
        </div>

        <!-- Final Grade Formula Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-trophy mr-2 text-teal-600"></i>
                Final Grade Formula
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Define how the final grade is calculated. Use PRELIM, MIDTERM, FINALS for term grades.
            </p>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="text" 
                           x-model="finalGradeFormula"
                           placeholder="e.g., PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 font-mono">
                </div>
                <button type="button" 
                        @click="testFormula(finalGradeFormula, { PRELIM: 85, MIDTERM: 88, FINALS: 90 })"
                        class="px-4 py-3 bg-teal-100 hover:bg-teal-200 rounded-lg text-sm font-medium">
                    <i class="fas fa-flask mr-2"></i>Test with Sample
                </button>
            </div>
        </div>

        <!-- Test Result Display -->
        <div x-show="testResult !== null" 
             x-transition
             class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <h4 class="text-sm font-semibold text-green-900 mb-2">
                <i class="fas fa-check-circle mr-2"></i>Test Result
            </h4>
            <p class="text-lg font-bold text-green-700" x-text="'Result: ' + testResult"></p>
        </div>

        <!-- Error Display -->
        <div x-show="error !== null" 
             x-transition
             class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <h4 class="text-sm font-semibold text-red-900 mb-2">
                <i class="fas fa-exclamation-triangle mr-2"></i>Error
            </h4>
            <p class="text-sm text-red-700" x-text="error"></p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between">
            <a href="{{ route('grade-matrix.customized.term', ['subject' => $subject, 'term' => $term]) }}" 
               class="px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" 
                    :disabled="saving"
                    class="px-6 py-3 text-white rounded-lg font-medium transition-colors"
                    style="background-color: #08695A;"
                    onmouseover="if(!this.disabled) this.style.backgroundColor='#065A4A';"
                    onmouseout="this.style.backgroundColor='#08695A';">
                <i class="fas fa-save mr-2"></i>
                <span x-text="saving ? 'Saving...' : 'Save Formulas'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function formulaConfig() {
    return {
        activityFormulas: @json($gradingConfig->custom_formulas['activity_formulas'] ?? []),
        quizFormula: '{{ $gradingConfig->custom_formulas['quiz_formula'] ?? '' }}',
        examFormula: '{{ $gradingConfig->custom_formulas['exam_formula'] ?? '' }}',
        termGradeFormula: '{{ $gradingConfig->custom_formulas['term_grade_formula'] ?? 'CS * 0.6 + EXAM * 0.4' }}',
        finalGradeFormula: '{{ $gradingConfig->custom_formulas['final_grade_formula'] ?? 'PRELIM * 0.3 + MIDTERM * 0.3 + FINALS * 0.4' }}',
        testResult: null,
        error: null,
        saving: false,

        async testFormula(formula, variables) {
            if (!formula) {
                this.error = 'Please enter a formula to test';
                return;
            }

            try {
                const response = await fetch('{{ route('formula.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ formula, variables })
                });

                const data = await response.json();

                if (data.success) {
                    this.testResult = data.result;
                    this.error = null;
                    setTimeout(() => this.testResult = null, 5000);
                } else {
                    this.error = data.message;
                    this.testResult = null;
                }
            } catch (err) {
                this.error = 'Failed to test formula: ' + err.message;
                this.testResult = null;
            }
        },

        async saveFormulas() {
            this.saving = true;
            this.error = null;

            try {
                const response = await fetch('{{ route('formula.save', ['subject' => $subject, 'term' => $term]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        activity_formulas: this.activityFormulas,
                        quiz_formula: this.quizFormula,
                        exam_formula: this.examFormula,
                        term_grade_formula: this.termGradeFormula,
                        final_grade_formula: this.finalGradeFormula
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Formulas saved successfully!');
                    window.location.href = '{{ route('grade-matrix.customized.term', ['subject' => $subject, 'term' => $term]) }}';
                } else {
                    this.error = data.message;
                }
            } catch (err) {
                this.error = 'Failed to save formulas: ' + err.message;
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endsection
