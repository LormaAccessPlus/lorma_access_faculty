@extends('layouts.admin')

@section('title', 'Grade Sheet - ' . $subject->subject_name)

@section('page-title', $subject->subject_name)

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <a href="{{ route('grading.index') }}" class="text-gray-500 hover:text-gray-700">Grading</a>
    </li>
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span class="text-gray-900">{{ $subject->subject_code }}</span>
    </li>
@endsection

@section('content')
<div x-data="{ activeTerm: '{{ $gradingClass->term }}' }">
    <!-- Term Tabs -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="flex border-b border-gray-200">
            @if($prelim)
                <button @click="window.location.href='{{ route('grading.grade-sheet', $prelim->id) }}'" 
                        class="flex-1 px-6 py-4 text-center font-medium transition-colors {{ $gradingClass->term === 'prelim' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    Prelim
                    @if($prelim->components->count() > 0)
                        <span class="ml-2 text-xs {{ $gradingClass->term === 'prelim' ? 'text-teal-100' : 'text-gray-500' }}">
                            {{ $prelim->components->sum(fn($c) => $c->weight_percentage) }}%
                        </span>
                    @else
                        <span class="ml-2 text-xs {{ $gradingClass->term === 'prelim' ? 'text-teal-100' : 'text-gray-500' }}">
                            0%
                        </span>
                    @endif
                </button>
            @endif
            
            @if($midterm)
                <button @click="window.location.href='{{ route('grading.grade-sheet', $midterm->id) }}'" 
                        class="flex-1 px-6 py-4 text-center font-medium transition-colors {{ $gradingClass->term === 'midterm' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    Midterm
                    @if($midterm->components->count() > 0)
                        <span class="ml-2 text-xs {{ $gradingClass->term === 'midterm' ? 'text-teal-100' : 'text-gray-500' }}">
                            {{ $midterm->components->sum(fn($c) => $c->weight_percentage) }}%
                        </span>
                    @else
                        <span class="ml-2 text-xs {{ $gradingClass->term === 'midterm' ? 'text-teal-100' : 'text-gray-500' }}">
                            0%
                        </span>
                    @endif
                </button>
            @endif
            
            @if($finals)
                <button @click="window.location.href='{{ route('grading.grade-sheet', $finals->id) }}'" 
                        class="flex-1 px-6 py-4 text-center font-medium transition-colors {{ $gradingClass->term === 'finals' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    Finals
                    @if($finals->components->count() > 0)
                        <span class="ml-2 text-xs {{ $gradingClass->term === 'finals' ? 'text-teal-100' : 'text-gray-500' }}">
                            {{ $finals->components->sum(fn($c) => $c->weight_percentage) }}%
                        </span>
                    @else
                        <span class="ml-2 text-xs {{ $gradingClass->term === 'finals' ? 'text-teal-100' : 'text-gray-500' }}">
                            0%
                        </span>
                    @endif
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-xl mr-3"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Grading Formula Configuration -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    <i class="fas fa-calculator text-teal-600 mr-2"></i>
                    Grading Formula Configuration
                </h3>
                <p class="text-sm text-gray-600 mb-4">Define how grades are computed in the grade table below</p>
                
                @if($gradingClass->components->count() > 0)
                    <div class="bg-white rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">{{ ucfirst($gradingClass->term) }} Term Formula</p>
                        <p class="text-lg font-semibold text-gray-900">
                            @foreach($gradingClass->components as $index => $component)
                                {{ $component->weight_percentage }}% {{ $component->component_name }}
                                @if($index < $gradingClass->components->count() - 1)
                                    <span class="text-gray-500">+</span>
                                @endif
                            @endforeach
                        </p>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No components configured yet
                        </p>
                    </div>
                @endif
            </div>
            <div class="flex gap-2">
                @if($subject->gcr_class_id)
                    <button onclick="fetchScoresFromGCR()" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Fetch Scores from GCR
                    </button>
                @endif
                <a href="{{ route('grading.configure', $gradingClass->id) }}" 
                   class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-cog mr-2"></i>
                    Configure
                </a>
            </div>
        </div>
    </div>

    @if($gradingClass->components->count() == 0)
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-6xl text-yellow-500"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Components Configured</h3>
            <p class="text-gray-600 mb-6">
                You need to configure grading components before you can enter grades.
            </p>
            <a href="{{ route('grading.configure', $gradingClass->id) }}" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700">
                <i class="fas fa-cog mr-2"></i>
                Configure Components Now
            </a>
        </div>
    @else
        <!-- Term Progress -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                        {{ ucfirst($gradingClass->term) }} Term Progress
                    </h3>
                    <p class="text-sm text-gray-600">
                        0 of {{ $students->count() }} students completed
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-900">0%</div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="openAddItemModal()" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-cog mr-2"></i>
                            Manage Activities
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-600 text-xl mr-3 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-blue-900 mb-1">{{ ucfirst($gradingClass->term) }} Grading Instructions</p>
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-keyboard mr-1"></i> Press <kbd class="px-2 py-1 bg-white rounded border border-blue-300">Enter</kbd> to save grades • Click cells to input scores
                    </p>
                </div>
            </div>
        </div>

        <!-- Grade Sheet Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th rowspan="2" class="sticky left-0 z-10 bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                Student Name
                            </th>
                            @foreach($gradingClass->components as $component)
                                <th colspan="{{ $component->items->count() + 2 }}" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    <div class="flex items-center justify-center gap-2">
                                        <span>{{ $component->component_name }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $component->weight_percentage }}%
                                        </span>
                                    </div>
                                </th>
                            @endforeach
                            <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">
                                <i class="fas fa-star mr-1"></i>Term Grade
                            </th>
                        </tr>
                        <tr>
                            @foreach($gradingClass->components as $component)
                                @foreach($component->items as $item)
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 border-r border-gray-100" style="min-width: 100px;">
                                        <div class="font-semibold text-gray-700">
                                            {{ $item->item_name }}
                                            @if($item->activity_id && optional($item->activity)->gcr_assignment_id)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-blue-500 text-white ml-1">G</span>
                                            @endif
                                        </div>
                                        <div class="text-gray-400 mt-1">/{{ $item->max_score }}</div>
                                        @if($item->date)
                                            <div class="text-gray-400 text-xs mt-1">{{ \Carbon\Carbon::parse($item->date)->format('M d') }}</div>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-gray-50 border-r border-gray-200">
                                    Total
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-gray-100 border-r border-gray-200">
                                    {{ $component->component_name }} Grade
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($students as $student)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="sticky left-0 z-10 bg-white px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-sm font-medium text-blue-600">{{ strtoupper(substr($student->student_name, 0, 1)) }}</span>
                                        </div>
                                        {{ $student->student_name }}
                                    </div>
                                </td>
                                @php
                                    $termGradeComponents = [];
                                @endphp
                                @foreach($gradingClass->components as $component)
                                    @php
                                        $rawTotal = 0;
                                        $componentTotal = 0;
                                        $componentCount = 0;
                                    @endphp
                                    @foreach($component->items as $item)
                                        @php
                                            $grade = $item->grades->where('student_mapping_id', $student->id)->first();
                                        @endphp
                                        <td class="px-3 py-4 text-center border-r border-gray-100">
                                            @php
                                                $isGCR = $item->activity_id && optional($item->activity)->gcr_assignment_id;
                                            @endphp
                                            <input type="number" 
                                                   class="grade-input w-20 px-2 py-1 text-center border rounded-lg transition-colors {{ $isGCR ? 'bg-blue-50 border-blue-300 cursor-not-allowed' : 'border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500' }}" 
                                                   data-grading-class="{{ $gradingClass->id }}"
                                                   data-student="{{ $student->id }}"
                                                   data-item="{{ $item->id }}"
                                                   value="{{ $grade->score ?? '' }}"
                                                   min="0" 
                                                   max="{{ $item->max_score }}"
                                                   step="0.01"
                                                   placeholder="0"
                                                   {{ $isGCR ? 'readonly' : '' }}
                                                   title="{{ $isGCR ? 'This grade is from Google Classroom and cannot be edited manually' : '' }}">
                                        </td>
                                        @php
                                            if ($grade && $grade->score !== null) {
                                                $rawTotal += $grade->score;
                                            }
                                            if ($grade && $grade->computed_score !== null) {
                                                $componentTotal += $grade->computed_score;
                                                $componentCount++;
                                            }
                                        @endphp
                                    @endforeach
                                    <!-- Raw Total Column -->
                                    <td class="px-3 py-4 text-center bg-gray-50 border-r border-gray-200">
                                        <span class="text-sm font-semibold text-gray-700">
                                            {{ number_format($rawTotal, 2) }}
                                        </span>
                                    </td>
                                    <!-- Component Grade Column (computed average) -->
                                    <td class="px-3 py-4 text-center bg-gray-100 border-r border-gray-200" data-component-weight="{{ $component->weight_percentage }}" data-component-id="{{ $component->id }}">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-semibold {{ $componentCount > 0 ? 'bg-blue-100 text-blue-800' : 'text-gray-400' }}">
                                            @if($componentCount > 0)
                                                {{ number_format($componentTotal / $componentCount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </td>
                                    @php
                                        if ($componentCount > 0) {
                                            $avg = $componentTotal / $componentCount;
                                            $termGradeComponents[] = $avg * ($component->weight_percentage / 100);
                                        }
                                    @endphp
                                @endforeach
                                <td class="px-6 py-4 text-center bg-blue-50">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-base font-bold {{ count($termGradeComponents) > 0 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-400' }}">
                                        @if(count($termGradeComponents) > 0)
                                            {{ number_format(array_sum($termGradeComponents), 2) }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <i class="fas fa-users text-4xl mb-3"></i>
                                        <p class="text-lg">No students found</p>
                                        <p class="text-sm">Please import students first</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>


<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeAddItemModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="" method="POST" id="addItemForm">
                @csrf
                <div class="bg-teal-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            <i class="fas fa-plus-circle mr-2"></i>Add Item to Component
                        </h3>
                        <button type="button" onclick="closeAddItemModal()" class="text-white hover:text-gray-200">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="bg-white px-6 py-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-puzzle-piece mr-1"></i> Component
                        </label>
                        <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" 
                                id="component_id" 
                                name="component_id" 
                                required>
                            @foreach($gradingClass->components as $component)
                                <option value="{{ $component->id }}">{{ $component->component_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag mr-1"></i> Item Name
                        </label>
                        <input type="text" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" 
                               name="item_name" 
                               placeholder="e.g., Quiz 1, Activity 2" 
                               required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-star mr-1"></i> Max Score
                        </label>
                        <input type="number" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" 
                               name="max_score" 
                               min="0" 
                               step="0.01" 
                               placeholder="e.g., 100" 
                               required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1"></i> Date (optional)
                        </label>
                        <input type="date" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" 
                               name="date">
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" onclick="closeAddItemModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-teal-600 hover:bg-teal-700">
                        <i class="fas fa-plus mr-1"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sticky {
    position: sticky;
}

.grade-input:focus {
    outline: none;
}

.grade-input.saving {
    border-color: #FCD34D;
    background-color: #FEF3C7;
}

.grade-input.saved {
    border-color: #10B981;
    background-color: #D1FAE5;
}

.grade-input.error {
    border-color: #EF4444;
    background-color: #FEE2E2;
}

kbd {
    font-family: monospace;
    font-size: 0.875rem;
}
</style>

<script>
// Modal functions
function openAddItemModal() {
    document.getElementById('addItemModal').classList.remove('hidden');
}

function closeAddItemModal() {
    document.getElementById('addItemModal').classList.add('hidden');
}

// Update form action when component is selected
document.getElementById('component_id').addEventListener('change', function() {
    const componentId = this.value;
    document.getElementById('addItemForm').action = `/grading/component/${componentId}/add-item`;
});

// Set initial action
if (document.getElementById('component_id').value) {
    document.getElementById('component_id').dispatchEvent(new Event('change'));
}

// Restore scroll position after reload
const scrollPos = sessionStorage.getItem('scrollPosition');
if (scrollPos) {
    window.scrollTo(0, parseInt(scrollPos));
    sessionStorage.removeItem('scrollPosition');
}

// Restore focus to the input that was edited
const lastStudent = sessionStorage.getItem('lastEditedStudent');
const lastItem = sessionStorage.getItem('lastEditedItem');
if (lastStudent && lastItem) {
    setTimeout(() => {
        const input = document.querySelector(`input[data-student="${lastStudent}"][data-item="${lastItem}"]`);
        if (input) {
            input.scrollIntoView({ behavior: 'instant', block: 'center' });
            input.focus();
        }
        sessionStorage.removeItem('lastEditedStudent');
        sessionStorage.removeItem('lastEditedItem');
    }, 100);
}

// Auto-save grades
document.querySelectorAll('.grade-input').forEach(input => {
    let timeout;
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        this.classList.add('saving');
        timeout = setTimeout(() => {
            saveGrade(this);
        }, 800);
    });
    
    // Save on Enter key
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(timeout);
            this.classList.add('saving');
            saveGrade(this);
        }
    });
});

function updateRowTotals(input) {
    const row = input.closest('tr');
    const allInputs = row.querySelectorAll('.grade-input');
    const totalCellsWithWeight = row.querySelectorAll('td.bg-gray-50[data-component-weight]');
    const termGradeCell = row.querySelector('td.bg-blue-50 span');
    
    // Recalculate each component total
    let currentInputs = [];
    let totalIndex = 0;
    
    allInputs.forEach((inp, index) => {
        currentInputs.push(inp);
        
        const nextTd = inp.closest('td').nextElementSibling;
        const isBeforeTotal = nextTd && nextTd.classList.contains('bg-gray-50');
        
        if (isBeforeTotal || index === allInputs.length - 1) {
            let total = 0;
            let count = 0;
            
            currentInputs.forEach(i => {
                const value = parseFloat(i.value);
                if (!isNaN(value) && value !== '') {
                    const maxScore = parseFloat(i.getAttribute('max'));
                    const computedScore = (value / maxScore) * 100;
                    total += computedScore;
                    count++;
                }
            });
            
            const avg = count > 0 ? (total / count) : null;
            
            if (totalCellsWithWeight[totalIndex]) {
                const span = totalCellsWithWeight[totalIndex].querySelector('span');
                if (span) {
                    span.textContent = avg !== null ? avg.toFixed(2) : '-';
                    if (count > 0) {
                        span.className = 'inline-flex items-center px-2 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800';
                    }
                }
            }
            
            currentInputs = [];
            totalIndex++;
        }
    });
    
    // Calculate term grade from ALL component totals (including ones not just updated)
    let termGrade = 0;
    let validComponents = 0;
    
    totalCellsWithWeight.forEach(totalCell => {
        const span = totalCell.querySelector('span');
        const weight = parseFloat(totalCell.dataset.componentWeight);
        const text = span.textContent.trim();
        
        // Skip if it's a dash or empty
        if (text === '-' || text === '') return;
        
        const avg = parseFloat(text);
        
        if (!isNaN(avg) && !isNaN(weight)) {
            termGrade += avg * (weight / 100);
            validComponents++;
        }
    });
    
    // Update term grade cell
    if (termGradeCell && validComponents > 0) {
        termGradeCell.textContent = termGrade.toFixed(2);
        termGradeCell.className = 'inline-flex items-center px-3 py-1 rounded-full text-base font-bold bg-blue-600 text-white';
    }
}

function saveGrade(input) {
    // Save which input was being edited
    const studentId = input.dataset.student;
    const itemId = input.dataset.item;
    sessionStorage.setItem('lastEditedStudent', studentId);
    sessionStorage.setItem('lastEditedItem', itemId);
    
    const data = {
        grading_class_id: input.dataset.gradingClass,
        student_mapping_id: input.dataset.student,
        component_item_id: input.dataset.item,
        score: input.value || null,
        _token: '{{ csrf_token() }}'
    };

    fetch('{{ route("grading.save-grade") }}', {
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
            input.classList.remove('saving');
            input.classList.add('saved');
            
            // Save scroll position and reload for accurate calculations
            sessionStorage.setItem('scrollPosition', window.pageYOffset);
            
            setTimeout(() => {
                location.reload();
            }, 300);
        } else {
            input.classList.remove('saving');
            input.classList.add('error');
            setTimeout(() => {
                input.classList.remove('error');
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        input.classList.remove('saving');
        input.classList.add('error');
        setTimeout(() => {
            input.classList.remove('error');
        }, 2000);
    });
}

function fetchScoresFromGCR() {
    if (!confirm('Fetch scores from Google Classroom? This will import grades for all activities linked to GCR assignments.')) {
        return;
    }
    
    fetch('{{ route('grading.fetch-scores', $gradingClass->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error fetching scores: ' + error);
    });
}
</script>

@endsection
