@extends('layouts.admin')

@section('title', 'Full Matrix - ' . $subject->subject_name)

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('grading.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i>
                        Grading
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500">{{ $subject->subject_code }}</span>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500">Full Matrix</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $subject->subject_code }} - {{ $subject->subject_name }}</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $subject->section }}</p>
                </div>
                <div class="flex gap-3">
                    <!-- Export Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                            <i class="fas fa-download mr-2"></i>
                            Export
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <a href="{{ route('grading.export-full-matrix-csv', $subject->id) }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-t-lg">
                                <i class="fas fa-file-csv text-green-600 mr-3"></i>
                                Export as CSV
                            </a>
                            <a href="{{ route('grading.export-full-matrix-pdf', $subject->id) }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-b-lg border-t border-gray-100">
                                <i class="fas fa-file-pdf text-red-600 mr-3"></i>
                                Export as PDF
                            </a>
                        </div>
                    </div>
                    
                    <button onclick="openFormulaModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                        <i class="fas fa-cog mr-2"></i>
                        Configure Weights
                    </button>
                    <!-- Grade Sheet Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                            <i class="fas fa-table mr-2"></i>
                            Grade Sheet
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="py-2">
                                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-100">
                                    Select Term
                                </div>
                                @foreach($gradingClasses as $gradingClass)
                                    <a href="{{ route('grading.grade-sheet', $gradingClass->id) }}" 
                                       class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-teal-50 hover:text-teal-700 transition-colors">
                                        <i class="fas fa-calendar-alt mr-3 text-teal-600"></i>
                                        <div>
                                            <div class="font-medium">{{ ucfirst($gradingClass->term) }} Term</div>
                                            <div class="text-xs text-gray-500">Grade entry & management</div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formula Display -->
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <div class="flex items-start gap-2">
                    <i class="fas fa-calculator text-blue-600 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-blue-900 mb-1">Final Rating Formula</p>
                        <div class="text-sm text-blue-800">
                            <span class="font-medium">Term Grade</span> = (Prelim × {{ $termWeights['prelim'] }}%) + (Midterm × {{ $termWeights['midterm'] }}%) + (Finals × {{ $termWeights['finals'] }}%)
                        </div>
                        <div class="text-sm text-blue-800 mt-1">
                            <span class="font-medium">Final Rating</span> = 
                            @if($matrixComponents->count() > 0)
                                (Term Grade × {{ $finalRatingFormula['final_grade'] ?? 80 }}%)
                                @foreach($matrixComponents as $component)
                                    + ({{ $component->component_name }} × {{ $finalRatingFormula[$component->component_name] ?? 0 }}%)
                                @endforeach
                            @else
                                Term Grade
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Matrix Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                        <tr>
                            <th class="sticky left-0 z-10 bg-gray-50 px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Student Name
                            </th>
                            @foreach($gradingClasses as $gradingClass)
                                @php
                                    $bgColor = $loop->index == 0 ? 'bg-green-50' : ($loop->index == 1 ? 'bg-yellow-50' : 'bg-purple-50');
                                @endphp
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider {{ $bgColor }}">
                                    <div class="text-gray-900">{{ ucfirst($gradingClass->term) }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5 font-normal">({{ $termWeights[$gradingClass->term] }}%)</div>
                                </th>
                            @endforeach
                            @if($matrixComponents->count() > 0)
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider bg-blue-50">
                                    <div class="text-gray-900">Term Grade</div>
                                    <div class="text-xs text-gray-500 mt-0.5 font-normal">({{ $finalRatingFormula['final_grade'] ?? 80 }}%)</div>
                                </th>
                            @endif
                            @foreach($matrixComponents as $component)
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider bg-orange-50">
                                    <div class="flex items-center justify-center gap-2">
                                        <div>
                                            <div class="text-gray-900">{{ $component->component_name }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5 font-normal">({{ $finalRatingFormula[$component->component_name] ?? 0 }}%) / {{ $component->max_score }} <i class="fas fa-edit text-xs cursor-pointer"></i></div>
                                        </div>
                                        <button onclick="deleteComponent({{ $component->id }})" class="text-red-500 hover:text-red-700" title="Delete component">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider bg-blue-100">
                                Final Grade
                                <div class="text-xs text-gray-500 mt-0.5 font-normal">(Raw)</div>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider bg-indigo-50">
                                Final Rating
                                <div class="text-xs text-gray-500 mt-0.5 font-normal">(Rounded)</div>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider bg-gray-50">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="sticky left-0 z-10 bg-white px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <div class="flex items-center">
                                    @php
                                        // For "LASTNAME, Firstname" format, get L and F initials
                                        $formattedName = $student->formatted_name;
                                        if (strpos($formattedName, ',') !== false) {
                                            $parts = explode(',', $formattedName);
                                            $lastname = trim($parts[0]);
                                            $firstname = trim($parts[1] ?? '');
                                            $initials = strtoupper(substr($lastname, 0, 1) . substr($firstname, 0, 1));
                                        } else {
                                            $initials = strtoupper(substr($formattedName, 0, 1));
                                        }
                                        $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500'];
                                        $colorIndex = ord($initials[0]) % count($colors);
                                    @endphp
                                    <div class="flex-shrink-0 h-10 w-10 {{ $colors[$colorIndex] }} rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-semibold text-white">{{ $initials }}</span>
                                    </div>
                                    <div class="font-medium text-gray-900">{{ $student->formatted_name }}</div>
                                </div>
                            </td>
                            @php
                                $allGrades = [];
                                $finalRating = 0;
                                $termGradesTotal = 0;
                                $termGradesCount = 0;
                            @endphp
                            @foreach($gradingClasses as $gradingClass)
                                @php
                                    // Calculate term grade for this student
                                    $termGrade = 0;
                                    $totalWeight = 0;
                                    
                                    foreach($gradingClass->components as $component) {
                                        if ($component->component_name === 'Exam') {
                                            // Get exam score
                                            $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                                                ->where('student_mapping_id', $student->id)
                                                ->where('component_id', $component->id)
                                                ->first();
                                            
                                            if ($examGrade && $examGrade->exam_score !== null) {
                                                // Use computed_score if available (applies configured formula)
                                                if ($examGrade->computed_score !== null) {
                                                    $examComputedScore = $examGrade->computed_score;
                                                } else {
                                                    // Fallback to raw percentage calculation
                                                    $examMaxScore = $component->exam_max_score ?? 100;
                                                    $examComputedScore = ($examGrade->exam_score / $examMaxScore) * 100;
                                                }
                                                $termGrade += $examComputedScore * ($component->weight_percentage / 100);
                                                $totalWeight += $component->weight_percentage;
                                            }
                                        } else {
                                            // Get average of component items
                                            $grades = \App\Models\StudentGrade::where('student_mapping_id', $student->id)
                                                ->whereIn('component_item_id', $component->items->pluck('id'))
                                                ->get();
                                            
                                            $total = 0;
                                            $count = 0;
                                            foreach ($grades as $grade) {
                                                if ($grade->computed_score !== null) {
                                                    $total += $grade->computed_score;
                                                    $count++;
                                                }
                                            }
                                            
                                            if ($count > 0) {
                                                $avg = $total / $count;
                                                $termGrade += $avg * ($component->weight_percentage / 100);
                                                $totalWeight += $component->weight_percentage;
                                            }
                                        }
                                    }
                                    
                                    $allGrades[$gradingClass->term] = $termGrade;
                                    // Accumulate term grades for Final Grade calculation
                                    $termGradesTotal += $termGrade * ($termWeights[$gradingClass->term] / 100);
                                    $termGradesCount++;
                                @endphp
                                <td class="px-6 py-4 text-center">
                                    @if($termGrade > 0)
                                        <span class="text-sm font-medium text-gray-900">{{ number_format($termGrade, 2) }}</span>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                            @endforeach
                            @php
                                // Calculate Final Grade from term grades
                                $finalGrade = $termGradesCount > 0 ? $termGradesTotal : 0;
                                
                                // Apply Final Grade weight to Final Rating (only if there are additional components)
                                if ($matrixComponents->count() > 0 && isset($finalRatingFormula['final_grade'])) {
                                    $finalRating += $finalGrade * ($finalRatingFormula['final_grade'] / 100);
                                } else {
                                    // If no additional components, Final Rating = Final Grade
                                    $finalRating = $finalGrade;
                                }
                            @endphp
                            @if($matrixComponents->count() > 0)
                                <td class="px-6 py-4 text-center bg-blue-50">
                                    @if($finalGrade > 0)
                                        <span class="text-base font-bold text-blue-900">{{ number_format($finalGrade, 2) }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            @endif
                            @foreach($matrixComponents as $component)
                                @php
                                    $score = $component->scores->where('student_mapping_id', $student->id)->first();
                                    $componentGrade = 0;
                                    if ($score && $score->score !== null) {
                                        // Apply formula if exists, otherwise use default (score/total)*100
                                        if ($component->formula) {
                                            try {
                                                $formula = str_replace(['score', 'total'], [$score->score, $component->max_score], $component->formula);
                                                $componentGrade = eval("return {$formula};");
                                            } catch (\Exception $e) {
                                                $componentGrade = ($score->score / $component->max_score) * 100;
                                            }
                                        } else {
                                            $componentGrade = ($score->score / $component->max_score) * 100;
                                        }
                                        $allGrades[$component->component_name] = $componentGrade;
                                        if (isset($finalRatingFormula[$component->component_name])) {
                                            $finalRating += $componentGrade * ($finalRatingFormula[$component->component_name] / 100);
                                        }
                                    }
                                @endphp
                                <td class="px-6 py-4 text-center bg-orange-50">
                                    <input type="number" 
                                           class="matrix-score-input w-20 px-2 py-1 text-center border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                           data-component="{{ $component->id }}"
                                           data-student="{{ $student->id }}"
                                           data-max-score="{{ $component->max_score }}"
                                           data-formula="{{ $component->formula ?? '' }}"
                                           value="{{ $score->score ?? '' }}"
                                           min="0"
                                           max="{{ $component->max_score }}"
                                           step="0.01"
                                           placeholder="0">
                                </td>
                            @endforeach
                            <td class="px-6 py-4 text-center bg-blue-50">
                                @if($finalRating > 0)
                                    <span class="text-base font-semibold text-blue-900">{{ number_format($finalRating, 2) }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    // Check if there's a saved final rating in the database
                                    $savedFinalRating = \App\Models\FinalRating::where('student_mapping_id', $student->id)
                                        ->where('subject_id', $subject->id)
                                        ->first();
                                    $displayRating = $savedFinalRating && $savedFinalRating->final_rating !== null 
                                        ? round($savedFinalRating->final_rating) 
                                        : round($finalRating);
                                @endphp
                                <input type="number" 
                                       class="final-rating-input w-20 px-2 py-1 text-center border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg font-bold"
                                       data-student="{{ $student->id }}"
                                       data-subject="{{ $subject->id }}"
                                       value="{{ $displayRating > 0 ? intval($displayRating) : '' }}"
                                       min="0"
                                       max="100"
                                       step="1"
                                       placeholder="0">
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    // Use the display rating (saved/edited value) for status check
                                    $isPassed = $displayRating >= 75;
                                @endphp
                                @if($displayRating > 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $isPassed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        <i class="fas fa-{{ $isPassed ? 'check' : 'times' }}-circle mr-1"></i>
                                        {{ $isPassed ? 'Passed' : 'Failed' }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $gradingClasses->count() + $matrixComponents->count() + ($matrixComponents->count() > 0 ? 5 : 4) }}" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-users text-gray-300 text-5xl mb-4"></i>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Students Found</h3>
                                    <p class="text-gray-600">Import students to see their grades in the matrix.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>

<!-- Configure Weights Modal -->
<div id="formulaModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeFormulaModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form id="formulaForm">
                @csrf
                <div class="bg-blue-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-cog mr-3"></i>Configure Final Rating Weights
                    </h3>
                </div>
                
                <div class="bg-white px-6 py-6 max-h-[70vh] overflow-y-auto">
                    <!-- Term Weights Section -->
                    <div class="mb-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prelim Weight (%)</label>
                                <input type="number" id="prelim_weight" class="term-weight-input block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="{{ $termWeights['prelim'] }}" min="0" max="100" step="0.01">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Midterm Weight (%)</label>
                                <input type="number" id="midterm_weight" class="term-weight-input block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="{{ $termWeights['midterm'] }}" min="0" max="100" step="0.01">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Finals Weight (%)</label>
                                <input type="number" id="finals_weight" class="term-weight-input block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="{{ $termWeights['finals'] }}" min="0" max="100" step="0.01">
                            </div>
                        </div>
                        <div class="mt-3 text-sm text-gray-600">
                            <span id="termTotal" class="font-medium">Term Total: 100%</span>
                        </div>
                    </div>

                    <hr class="my-6">

                    <!-- Additional Components Section -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Additional Component</h4>
                            <button type="button" onclick="addAdditionalComponent()" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                + Add Component
                            </button>
                        </div>
                        
                        <div id="additionalComponentsContainer">
                            @foreach($matrixComponents as $component)
                                <div class="additional-component mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200" data-component-id="{{ $component->id }}">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium text-gray-700">Component Name</span>
                                        <button type="button" onclick="removeExistingComponent(this, {{ $component->id }})" class="text-red-600 hover:text-red-700 text-sm">
                                            - Remove Component
                                        </button>
                                    </div>
                                    <input type="text" class="component-name-input block w-full px-4 py-2 mb-3 border border-gray-300 rounded-lg bg-gray-100" 
                                           value="{{ $component->component_name }}" readonly>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight (%)</label>
                                    <input type="number" class="component-weight-input block w-full px-4 py-2 mb-3 border-2 border-blue-400 rounded-lg focus:ring-2 focus:ring-blue-500" 
                                           data-component="{{ $component->component_name }}" 
                                           value="{{ $finalRatingFormula['components'][$component->component_name] ?? $finalRatingFormula[$component->component_name] ?? 0 }}" 
                                           min="0" max="100" step="0.01">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calculator text-red-500 mr-1"></i> Formula <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" class="component-formula-input block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm" 
                                           data-component="{{ $component->component_name }}"
                                           value="{{ $component->formula ?? '' }}" 
                                           placeholder="e.g., score/total*60+40">
                                    <p class="mt-1 text-xs text-red-600 font-medium">Required: Use 'score' and 'total' as variables for grade computation</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Total Warning -->
                    <div id="totalWarning" class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 hidden">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-400 mt-0.5"></i>
                            <p class="ml-3 text-sm text-yellow-700 font-medium">Total must equal 100%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
                    <button type="button" onclick="closeFormulaModal()" 
                            class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2.5 rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Configure Weights Modal
function openFormulaModal() {
    document.getElementById('formulaModal').classList.remove('hidden');
    updateConfigTotal();
}

function closeFormulaModal() {
    document.getElementById('formulaModal').classList.add('hidden');
}

// Add additional component
function addAdditionalComponent() {
    const container = document.getElementById('additionalComponentsContainer');
    const div = document.createElement('div');
    div.className = 'additional-component mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200';
    div.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-700">Component Name</span>
            <button type="button" onclick="removeAdditionalComponent(this)" class="text-blue-600 hover:text-blue-700 text-sm">
                - Remove Component
            </button>
        </div>
        <input type="text" class="component-name-input block w-full px-4 py-2 mb-3 border border-gray-300 rounded-lg" 
               placeholder="e.g., Comprehensive Exam">
        <label class="block text-sm font-medium text-gray-700 mb-2">Weight (%)</label>
        <input type="number" class="component-weight-input block w-full px-4 py-2 mb-3 border-2 border-blue-400 rounded-lg" 
               value="0" min="0" max="100" step="0.01">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fas fa-calculator text-red-500 mr-1"></i> Formula <span class="text-red-500">*</span>
        </label>
        <input type="text" class="component-formula-input block w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm" 
               placeholder="e.g., score/total*60+40" required>
        <p class="mt-1 text-xs text-red-600 font-medium">Required: Use 'score' and 'total' as variables for grade computation</p>
    `;
    container.appendChild(div);
    
    // Add event listener
    div.querySelector('.component-weight-input').addEventListener('input', updateConfigTotal);
}

function removeAdditionalComponent(btn) {
    btn.closest('.additional-component').remove();
    updateConfigTotal();
}

function removeExistingComponent(btn, componentId) {
    if (!confirm('Are you sure you want to remove this component? All scores will be deleted.')) {
        return;
    }
    
    fetch(`/grading/matrix-component/${componentId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.closest('.additional-component').remove();
            updateConfigTotal();
        }
    });
}

// Update total
document.querySelectorAll('.term-weight-input, .component-weight-input').forEach(input => {
    input.addEventListener('input', updateConfigTotal);
});

function updateConfigTotal() {
    // Calculate term total
    let termTotal = 0;
    document.querySelectorAll('.term-weight-input').forEach(input => {
        termTotal += parseFloat(input.value) || 0;
    });
    document.getElementById('termTotal').textContent = `Term Total: ${termTotal.toFixed(2)}%`;
    
    // Calculate component total (Term Grade + Additional Components)
    let componentTotal = 0;
    
    // Term Grade weight (always 100% - sum of additional components)
    let additionalTotal = 0;
    document.querySelectorAll('.component-weight-input').forEach(input => {
        additionalTotal += parseFloat(input.value) || 0;
    });
    
    const termGradeWeight = 100 - additionalTotal;
    componentTotal = 100;
    
    // Show/hide warning
    const warning = document.getElementById('totalWarning');
    if (termTotal !== 100 || componentTotal !== 100) {
        warning.classList.remove('hidden');
    } else {
        warning.classList.add('hidden');
    }
}

// Save configuration
document.getElementById('formulaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validate term weights
    const prelim = parseFloat(document.getElementById('prelim_weight').value) || 0;
    const midterm = parseFloat(document.getElementById('midterm_weight').value) || 0;
    const finals = parseFloat(document.getElementById('finals_weight').value) || 0;
    
    if (prelim + midterm + finals !== 100) {
        alert('Term weights (Prelim + Midterm + Finals) must equal 100%');
        return;
    }
    
    // Collect additional components
    const components = [];
    const newComponents = [];
    const componentFormulas = {};
    let additionalTotal = 0;
    
    document.querySelectorAll('.additional-component').forEach(comp => {
        const nameInput = comp.querySelector('.component-name-input');
        const name = nameInput.value.trim();
        const weight = parseFloat(comp.querySelector('.component-weight-input').value) || 0;
        const formulaInput = comp.querySelector('.component-formula-input');
        const formula = formulaInput ? formulaInput.value.trim() : '';
        
        if (name && weight > 0) {
            // Validate that formula is provided
            if (!formula) {
                alert(`Formula is required for component: ${name}`);
                formulaInput.focus();
                return;
            }
            
            components.push({ name, weight });
            additionalTotal += weight;
            
            // Store formula for this component
            componentFormulas[name] = formula;
            
            // Check if this is a new component (not readonly)
            if (!nameInput.hasAttribute('readonly')) {
                newComponents.push({ component_name: name, max_score: 100, formula: formula });
            }
        }
    });
    
    // Add Term Grade component
    const termGradeWeight = 100 - additionalTotal;
    if (termGradeWeight < 0) {
        alert('Additional component weights exceed 100%');
        return;
    }
    
    components.unshift({ name: 'final_grade', weight: termGradeWeight });
    
    console.log('Saving configuration:', {
        components,
        term_weights: { prelim, midterm, finals },
        newComponents
    });
    
    try {
        // First, create new components in the database
        for (const comp of newComponents) {
            console.log('Creating component:', comp);
            const createResponse = await fetch('{{ route("grading.add-matrix-component", $subject->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(comp)
            });
            
            console.log('Create response status:', createResponse.status);
            
            const createResponseText = await createResponse.text();
            console.log('Create raw response:', createResponseText.substring(0, 500));
            
            if (!createResponse.ok) {
                console.error('Create error response:', createResponseText);
                alert('Error creating component: ' + comp.name + '. Status: ' + createResponse.status);
                return;
            }
            
            let createData;
            try {
                createData = JSON.parse(createResponseText);
            } catch (e) {
                console.error('Failed to parse create response:', e);
                console.error('Response was:', createResponseText);
                alert('Server returned invalid response when creating component. Check console.');
                return;
            }
            
            console.log('Create response:', createData);
            
            if (!createData.success) {
                alert('Error creating component: ' + comp.name);
                return;
            }
        }
        
        // Then save the formula and component formulas
        const formulaData = { 
            components,
            term_weights: { prelim, midterm, finals },
            component_formulas: componentFormulas
        };
        
        console.log('Sending formula data:', formulaData);
        
        const response = await fetch('{{ route("grading.update-final-rating-formula", $subject->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(formulaData)
        });
        
        console.log('Formula response status:', response.status);
        console.log('Formula response headers:', response.headers.get('content-type'));
        
        const responseText = await response.text();
        console.log('Formula raw response:', responseText.substring(0, 500));
        
        if (!response.ok) {
            console.error('Formula error response:', responseText);
            alert('Error saving formula. Status: ' + response.status);
            return;
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            console.error('Response was:', responseText);
            alert('Server returned invalid response. Check console.');
            return;
        }
        console.log('Save response:', data);
        
        if (data.success) {
            location.reload();
        } else {
            console.error('Save failed:', data);
            alert('Error: ' + (data.message || 'Failed to save configuration'));
        }
    } catch (error) {
        console.error('Error details:', error);
        alert('Error saving configuration: ' + error.message);
    }
});

// Save matrix component scores
document.querySelectorAll('.matrix-score-input').forEach(input => {
    let timeout;
    input.addEventListener('input', function() {
        // Update computed grade display immediately
        updateComputedGrade(this);
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            saveMatrixScore(this);
        }, 800);
    });
});

function updateComputedGrade(input) {
    const score = parseFloat(input.value);
    const maxScore = parseFloat(input.dataset.maxScore);
    const formula = input.dataset.formula;
    
    // Find or create the computed grade display
    const container = input.parentElement;
    let gradeDisplay = container.querySelector('.computed-grade-display');
    
    if (!gradeDisplay) {
        gradeDisplay = document.createElement('div');
        gradeDisplay.className = 'computed-grade-display text-xs font-semibold text-orange-700 bg-orange-100 px-2 py-0.5 rounded';
        container.appendChild(gradeDisplay);
    }
    
    if (isNaN(score) || score === 0 || isNaN(maxScore)) {
        gradeDisplay.style.display = 'none';
        return;
    }
    
    let computedGrade;
    if (formula && formula.trim() !== '') {
        try {
            // Replace 'score' and 'total' in formula
            const expression = formula.replace(/score/g, score).replace(/total/g, maxScore);
            computedGrade = eval(expression);
        } catch (e) {
            // Fallback to default calculation
            computedGrade = (score / maxScore) * 100;
        }
    } else {
        computedGrade = (score / maxScore) * 100;
    }
    
    gradeDisplay.textContent = computedGrade.toFixed(2) + '%';
    gradeDisplay.style.display = 'block';
}

function saveMatrixScore(input) {
    const data = {
        student_mapping_id: input.dataset.student,
        score: input.value || null
    };
    
    fetch(`/grading/matrix-component/${input.dataset.component}/save-score`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.classList.add('border-green-500');
            setTimeout(() => {
                input.classList.remove('border-green-500');
                location.reload();
            }, 500);
        }
    });
}

// Save final rating
document.querySelectorAll('.final-rating-input').forEach(input => {
    let timeout;
    input.addEventListener('input', function() {
        // Update status badge immediately
        updateStatusBadge(this);
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            saveFinalRating(this);
        }, 800);
    });
});

function updateStatusBadge(input) {
    const rating = parseFloat(input.value) || 0;
    const row = input.closest('tr');
    const statusCell = row.querySelector('td:last-child');
    
    if (rating > 0) {
        const isPassed = rating >= 75;
        const badgeClass = isPassed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        const icon = isPassed ? 'check' : 'times';
        const text = isPassed ? 'Passed' : 'Failed';
        
        statusCell.innerHTML = `
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${badgeClass}">
                <i class="fas fa-${icon}-circle mr-1"></i>
                ${text}
            </span>
        `;
    } else {
        statusCell.innerHTML = '<span class="text-gray-400 text-sm">-</span>';
    }
}

function saveFinalRating(input) {
    const data = {
        student_mapping_id: input.dataset.student,
        subject_id: input.dataset.subject,
        final_rating: input.value || null
    };
    
    fetch('{{ route("grading.save-final-rating") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success feedback
            input.classList.add('border-green-500');
            setTimeout(() => {
                input.classList.remove('border-green-500');
            }, 1000);
        } else {
            alert('Error saving final rating: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving final rating');
    });
}
</script>
@endsection
