@extends('layouts.admin')

@section('title', 'Configure Grading - ' . $gradingClass->class_name)

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">Configure Grading</h2>
            <p class="text-muted">{{ $gradingClass->class_name }} - {{ ucfirst($gradingClass->term) }}</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('grading.save-configuration', $gradingClass->id) }}" method="POST" id="configForm">
        @csrf
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Grading Components</h5>
                <button type="button" class="btn btn-sm btn-primary" onclick="addComponent()">
                    <i class="bi bi-plus"></i> Add Component
                </button>
            </div>
            <div class="card-body">
                <div id="componentsContainer">
                    @if($gradingClass->components->count() > 0)
                        @foreach($gradingClass->components as $index => $component)
                            <div class="component-row mb-3 p-3 border rounded" data-index="{{ $index }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Component Name</label>
                                        <input type="text" class="form-control" name="components[{{ $index }}][name]" 
                                               value="{{ $component->component_name }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="components[{{ $index }}][type]" required>
                                            <option value="activity" {{ $component->component_type == 'activity' ? 'selected' : '' }}>Activity</option>
                                            <option value="quiz" {{ $component->component_type == 'quiz' ? 'selected' : '' }}>Quiz</option>
                                            <option value="exam" {{ $component->component_type == 'exam' ? 'selected' : '' }}>Exam</option>
                                            <option value="attendance" {{ $component->component_type == 'attendance' ? 'selected' : '' }}>Attendance</option>
                                            <option value="custom" {{ $component->component_type == 'custom' ? 'selected' : '' }}>Custom</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Weight (%)</label>
                                        <input type="number" class="form-control weight-input" name="components[{{ $index }}][weight]" 
                                               value="{{ $component->weight_percentage }}" min="0" max="100" step="0.01" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Formula (optional)</label>
                                        <input type="text" class="form-control" name="components[{{ $index }}][formula]" 
                                               value="{{ $component->formula }}" placeholder="e.g., score / total * 60 + 40">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeComponent(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Default components -->
                        <div class="component-row mb-3 p-3 border rounded" data-index="0">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Component Name</label>
                                    <input type="text" class="form-control" name="components[0][name]" value="Activities" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="components[0][type]" required>
                                        <option value="activity" selected>Activity</option>
                                        <option value="quiz">Quiz</option>
                                        <option value="exam">Exam</option>
                                        <option value="attendance">Attendance</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Weight (%)</label>
                                    <input type="number" class="form-control weight-input" name="components[0][weight]" 
                                           value="15" min="0" max="100" step="0.01" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Formula (optional)</label>
                                    <input type="text" class="form-control" name="components[0][formula]" 
                                           placeholder="e.g., score / total * 60 + 40">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeComponent(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Total Weight:</strong> <span id="totalWeight">0</span>% 
                    <span id="weightWarning" class="text-danger ms-2" style="display: none;">Must equal 100%</span>
                </div>

                <div class="alert alert-secondary">
                    <strong>Formula Variables:</strong>
                    <ul class="mb-0">
                        <li><code>score</code> - Student's raw score</li>
                        <li><code>total</code> - Maximum possible score</li>
                        <li>Example: <code>score / total * 60 + 40</code> converts to 60-100 scale</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Term Grade Formula</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Formula (optional)</label>
                    <input type="text" class="form-control" name="term_formula" 
                           value="{{ $gradingClass->term_formula['formula'] ?? '' }}"
                           placeholder="Leave empty to use weighted average of components">
                    <small class="text-muted">
                        If left empty, term grade will be calculated as: (Component1 × Weight1) + (Component2 × Weight2) + ...
                    </small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('grading.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Configuration</button>
        </div>
    </form>
</div>

<script>
let componentIndex = {{ $gradingClass->components->count() > 0 ? $gradingClass->components->count() : 1 }};

function addComponent() {
    const container = document.getElementById('componentsContainer');
    const newComponent = `
        <div class="component-row mb-3 p-3 border rounded" data-index="${componentIndex}">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Component Name</label>
                    <input type="text" class="form-control" name="components[${componentIndex}][name]" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="components[${componentIndex}][type]" required>
                        <option value="activity">Activity</option>
                        <option value="quiz">Quiz</option>
                        <option value="exam">Exam</option>
                        <option value="attendance">Attendance</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Weight (%)</label>
                    <input type="number" class="form-control weight-input" name="components[${componentIndex}][weight]" 
                           min="0" max="100" step="0.01" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Formula (optional)</label>
                    <input type="text" class="form-control" name="components[${componentIndex}][formula]" 
                           placeholder="e.g., score / total * 60 + 40">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeComponent(this)">
                        <i class="bi bi-trash"></i>
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
    const saveBtn = document.getElementById('saveBtn');
    
    if (Math.abs(total - 100) > 0.01) {
        warning.style.display = 'inline';
        saveBtn.disabled = true;
    } else {
        warning.style.display = 'none';
        saveBtn.disabled = false;
    }
}

// Update total weight on input
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('weight-input')) {
        updateTotalWeight();
    }
});

// Initial calculation
updateTotalWeight();
</script>
@endsection
