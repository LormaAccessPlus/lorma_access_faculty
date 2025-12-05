@extends('layouts.admin')

@section('title', 'Grade Sheet - ' . $gradingClass->class_name)

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-0">{{ $gradingClass->class_name }}</h2>
            <p class="text-muted">{{ ucfirst($gradingClass->term) }} - Grade Sheet</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('grading.configure', $gradingClass->id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-gear"></i> Configure
            </a>
            <a href="{{ route('grading.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($gradingClass->components->count() == 0)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No components configured yet. 
            <a href="{{ route('grading.configure', $gradingClass->id) }}">Configure grading components</a> first.
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="align-middle">Student Name</th>
                                @foreach($gradingClass->components as $component)
                                    <th colspan="{{ $component->items->count() + 1 }}" class="text-center">
                                        {{ $component->component_name }} ({{ $component->weight_percentage }}%)
                                    </th>
                                @endforeach
                                <th rowspan="2" class="align-middle">Term Grade</th>
                            </tr>
                            <tr>
                                @foreach($gradingClass->components as $component)
                                    @foreach($component->items as $item)
                                        <th class="text-center" style="min-width: 80px;">
                                            {{ $item->item_name }}<br>
                                            <small class="text-muted">/{{ $item->max_score }}</small>
                                        </th>
                                    @endforeach
                                    <th class="text-center bg-light">Total</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>{{ $student->student_name }}</td>
                                    @php
                                        $termGradeComponents = [];
                                    @endphp
                                    @foreach($gradingClass->components as $component)
                                        @php
                                            $componentTotal = 0;
                                            $componentCount = 0;
                                        @endphp
                                        @foreach($component->items as $item)
                                            @php
                                                $grade = $item->grades->where('student_mapping_id', $student->id)->first();
                                            @endphp
                                            <td class="text-center">
                                                <input type="number" 
                                                       class="form-control form-control-sm text-center grade-input" 
                                                       data-grading-class="{{ $gradingClass->id }}"
                                                       data-student="{{ $student->id }}"
                                                       data-item="{{ $item->id }}"
                                                       value="{{ $grade->score ?? '' }}"
                                                       min="0" 
                                                       max="{{ $item->max_score }}"
                                                       step="0.01"
                                                       style="width: 70px;">
                                            </td>
                                            @php
                                                if ($grade && $grade->computed_score !== null) {
                                                    $componentTotal += $grade->computed_score;
                                                    $componentCount++;
                                                }
                                            @endphp
                                        @endforeach
                                        <td class="text-center bg-light">
                                            @if($componentCount > 0)
                                                {{ number_format($componentTotal / $componentCount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @php
                                            if ($componentCount > 0) {
                                                $avg = $componentTotal / $componentCount;
                                                $termGradeComponents[] = $avg * ($component->weight_percentage / 100);
                                            }
                                        @endphp
                                    @endforeach
                                    <td class="text-center fw-bold">
                                        @if(count($termGradeComponents) > 0)
                                            {{ number_format(array_sum($termGradeComponents), 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100" class="text-center text-muted">
                                        No students found. Please import students first.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="bi bi-plus"></i> Add Item to Component
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item to Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="addItemForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Component</label>
                        <select class="form-select" id="component_id" name="component_id" required>
                            @foreach($gradingClass->components as $component)
                                <option value="{{ $component->id }}">{{ $component->component_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" name="item_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Max Score</label>
                        <input type="number" class="form-control" name="max_score" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date (optional)</label>
                        <input type="date" class="form-control" name="date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Update form action when component is selected
document.getElementById('component_id').addEventListener('change', function() {
    const componentId = this.value;
    document.getElementById('addItemForm').action = `/grading/component/${componentId}/add-item`;
});

// Set initial action
if (document.getElementById('component_id').value) {
    document.getElementById('component_id').dispatchEvent(new Event('change'));
}

// Auto-save grades
document.querySelectorAll('.grade-input').forEach(input => {
    let timeout;
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            saveGrade(this);
        }, 500);
    });
});

function saveGrade(input) {
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
            input.classList.add('border-success');
            setTimeout(() => {
                input.classList.remove('border-success');
                location.reload(); // Reload to update calculations
            }, 500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        input.classList.add('border-danger');
    });
}
</script>
@endsection
