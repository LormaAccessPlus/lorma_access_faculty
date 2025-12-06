@extends('layouts.admin')

@section('title', 'Configure Grading - ' . $gradingClass->class_name)

@section('page-title', 'Configure Grading')

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <a href="{{ route('grading.index') }}" class="text-gray-500 hover:text-gray-700">Grading</a>
    </li>
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span class="text-gray-900">Configure</span>
    </li>
@endsection

@section('content')
<!-- Class Info Header -->
<div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold mb-2">{{ $gradingClass->class_name }}</h2>
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white bg-opacity-20">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    {{ ucfirst($gradingClass->term) }}
                </span>
                <span class="text-blue-100">
                    <i class="fas fa-users mr-1"></i>
                    {{ $gradingClass->subject->studentMappings->count() }} Students
                </span>
            </div>
        </div>
        <div class="text-right">
            <a href="{{ route('grading.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Grading
            </a>
        </div>
    </div>
</div>

<form action="{{ route('grading.save-configuration', $gradingClass->id) }}" method="POST" id="configForm">
    @csrf
    
    <!-- Grading Components Section -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-puzzle-piece text-blue-600 mr-2"></i>
                        Grading Components
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Define the components that make up the term grade</p>
                </div>
                <button type="button" onclick="addComponent()" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Add Component
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div id="componentsContainer" class="space-y-4">
                @if($gradingClass->components->count() > 0)
                    @foreach($gradingClass->components as $index => $component)
                        <div class="component-row bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow" data-index="{{ $index }}">
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-tag text-gray-400 mr-1"></i> Component Name
                                    </label>
                                    <input type="text" 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           name="components[{{ $index }}][name]" 
                                           value="{{ $component->component_name }}" 
                                           required>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-list text-gray-400 mr-1"></i> Type
                                    </label>
                                    <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            name="components[{{ $index }}][type]" 
                                            required>
                                        <option value="activity" {{ $component->component_type == 'activity' ? 'selected' : '' }}>Activity</option>
                                        <option value="quiz" {{ $component->component_type == 'quiz' ? 'selected' : '' }}>Quiz</option>
                                        <option value="class_standing" {{ $component->component_type == 'class_standing' ? 'selected' : '' }}>Class Standing (Activities + Quizzes)</option>
                                        <option value="exam" {{ $component->component_type == 'exam' ? 'selected' : '' }}>Exam</option>
                                        <option value="attendance" {{ $component->component_type == 'attendance' ? 'selected' : '' }}>Attendance</option>
                                        <option value="custom" {{ $component->component_type == 'custom' ? 'selected' : '' }}>Custom</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-percentage text-gray-400 mr-1"></i> Weight (%)
                                    </label>
                                    <input type="number" 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 weight-input" 
                                           name="components[{{ $index }}][weight]" 
                                           value="{{ $component->weight_percentage }}" 
                                           min="0" 
                                           max="100" 
                                           step="0.01" 
                                           required>
                                </div>
                                <div class="col-span-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calculator text-gray-400 mr-1"></i> Formula (optional)
                                    </label>
                                    <input type="text" 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           name="components[{{ $index }}][formula]" 
                                           value="{{ $component->formula }}" 
                                           placeholder="e.g., score / total * 60 + 40">
                                </div>
                                <div class="col-span-1 flex items-end">
                                    <button type="button" 
                                            onclick="removeComponent(this)" 
                                            class="w-full px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Default component -->
                    <div class="component-row bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow" data-index="0">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-tag text-gray-400 mr-1"></i> Component Name
                                </label>
                                <input type="text" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       name="components[0][name]" 
                                       value="Activities" 
                                       required>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-list text-gray-400 mr-1"></i> Type
                                </label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                        name="components[0][type]" 
                                        required>
                                    <option value="activity" selected>Activity</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="exam">Exam</option>
                                    <option value="attendance">Attendance</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-percentage text-gray-400 mr-1"></i> Weight (%)
                                </label>
                                <input type="number" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 weight-input" 
                                       name="components[0][weight]" 
                                       value="15" 
                                       min="0" 
                                       max="100" 
                                       step="0.01" 
                                       required>
                            </div>
                            <div class="col-span-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calculator text-gray-400 mr-1"></i> Formula (optional)
                                </label>
                                <input type="text" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       name="components[0][formula]" 
                                       placeholder="e.g., score / total * 60 + 40">
                            </div>
                            <div class="col-span-1 flex items-end">
                                <button type="button" 
                                        onclick="removeComponent(this)" 
                                        class="w-full px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Total Weight Display -->
            <div class="mt-6 p-4 bg-blue-50 border-2 border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-balance-scale text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Total Weight</p>
                            <p class="text-3xl font-bold text-blue-600">
                                <span id="totalWeight">0</span>%
                            </p>
                        </div>
                    </div>
                    <div id="weightWarning" class="hidden">
                        <div class="flex items-center px-4 py-2 bg-red-100 text-red-800 rounded-lg">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span class="font-medium">Must equal 100%</span>
                        </div>
                    </div>
                    <div id="weightSuccess" class="hidden">
                        <div class="flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-lg">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="font-medium">Perfect!</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formula Help -->
            <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-900 mb-2">
                    <i class="fas fa-info-circle text-blue-600 mr-1"></i> Formula Variables
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700">
                    <div class="flex items-start">
                        <code class="px-2 py-1 bg-gray-200 rounded text-xs mr-2">score</code>
                        <span>Student's raw score</span>
                    </div>
                    <div class="flex items-start">
                        <code class="px-2 py-1 bg-gray-200 rounded text-xs mr-2">total</code>
                        <span>Maximum possible score</span>
                    </div>
                </div>
                <div class="mt-2 p-2 bg-blue-50 rounded">
                    <p class="text-xs text-gray-600">
                        <strong>Example:</strong> <code class="bg-white px-1 rounded">score / total * 60 + 40</code> 
                        converts to 60-100 scale
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Term Grade Formula Section -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-function text-purple-600 mr-2"></i>
                Term Grade Formula
            </h3>
            <p class="text-sm text-gray-600 mt-1">Optional: Override the default weighted average calculation</p>
        </div>
        
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calculator text-gray-400 mr-1"></i> Custom Formula (optional)
                </label>
                <input type="text" 
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-lg font-mono" 
                       name="term_formula" 
                       value="{{ $gradingClass->term_formula['formula'] ?? '' }}"
                       placeholder="Leave empty to use weighted average of components">
            </div>
            
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-purple-900 mb-2">
                    <i class="fas fa-lightbulb mr-1"></i> Default Calculation
                </h4>
                <p class="text-sm text-purple-800">
                    If left empty, the term grade will be calculated as:
                </p>
                <div class="mt-2 p-3 bg-white rounded border border-purple-200">
                    <code class="text-sm text-purple-900">
                        (Component1 × Weight1) + (Component2 × Weight2) + ...
                    </code>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between bg-white rounded-lg shadow-sm p-6">
        <a href="{{ route('grading.index') }}" 
           class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            <i class="fas fa-times mr-2"></i>
            Cancel
        </a>
        
        <div class="flex items-center gap-4">
            <div id="saveStatus" class="text-sm text-gray-600 hidden">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Saving configuration...
            </div>
            <button type="submit" 
                    id="saveBtn"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-save mr-2"></i>
                Save Configuration
            </button>
        </div>
    </div>
</form>

<script>
let componentIndex = {{ $gradingClass->components->count() > 0 ? $gradingClass->components->count() : 1 }};

function addComponent() {
    const container = document.getElementById('componentsContainer');
    const newComponent = `
        <div class="component-row bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow" data-index="${componentIndex}">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag text-gray-400 mr-1"></i> Component Name
                    </label>
                    <input type="text" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           name="components[${componentIndex}][name]" 
                           required>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-list text-gray-400 mr-1"></i> Type
                    </label>
                    <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                            name="components[${componentIndex}][type]" 
                            required>
                        <option value="activity">Activity</option>
                        <option value="quiz">Quiz</option>
                        <option value="class_standing">Class Standing (Activities + Quizzes)</option>
                        <option value="exam">Exam</option>
                        <option value="attendance">Attendance</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-percentage text-gray-400 mr-1"></i> Weight (%)
                    </label>
                    <input type="number" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 weight-input" 
                           name="components[${componentIndex}][weight]" 
                           min="0" 
                           max="100" 
                           step="0.01" 
                           required>
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calculator text-gray-400 mr-1"></i> Formula (optional)
                    </label>
                    <input type="text" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           name="components[${componentIndex}][formula]" 
                           placeholder="e.g., score / total * 60 + 40">
                </div>
                <div class="col-span-1 flex items-end">
                    <button type="button" 
                            onclick="removeComponent(this)" 
                            class="w-full px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', newComponent);
    componentIndex++;
    updateTotalWeight();
}

function removeComponent(btn) {
    if (document.querySelectorAll('.component-row').length > 1) {
        btn.closest('.component-row').remove();
        updateTotalWeight();
    } else {
        alert('You must have at least one component.');
    }
}

function updateTotalWeight() {
    const weights = document.querySelectorAll('.weight-input');
    let total = 0;
    weights.forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    
    document.getElementById('totalWeight').textContent = total.toFixed(2);
    const warning = document.getElementById('weightWarning');
    const success = document.getElementById('weightSuccess');
    const saveBtn = document.getElementById('saveBtn');
    
    if (Math.abs(total - 100) > 0.01) {
        warning.classList.remove('hidden');
        success.classList.add('hidden');
        saveBtn.disabled = true;
    } else {
        warning.classList.add('hidden');
        success.classList.remove('hidden');
        saveBtn.disabled = false;
    }
}

// Update total weight on input
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('weight-input')) {
        updateTotalWeight();
    }
});

// Form submission
document.getElementById('configForm').addEventListener('submit', function() {
    document.getElementById('saveStatus').classList.remove('hidden');
    document.getElementById('saveBtn').disabled = true;
});

// Initial calculation
updateTotalWeight();
</script>

@endsection
