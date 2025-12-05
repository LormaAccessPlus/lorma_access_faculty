@extends('layouts.admin')

@section('title', 'Grading')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-0">Grading</h2>
            <p class="text-muted">Manage your customizable grading sheets</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="bi bi-plus-circle"></i> Add Class
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        @forelse($gradingClasses as $class)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $class->class_name }}</h5>
                        <p class="card-text">
                            <span class="badge bg-info text-dark">{{ ucfirst($class->term) }}</span>
                        </p>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong>Components:</strong> {{ $class->components->count() }}<br>
                                <strong>Items:</strong> {{ $class->components->sum(fn($c) => $c->items->count()) }}
                            </small>
                        </div>

                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('grading.grade-sheet', $class->id) }}" class="btn btn-primary">
                                <i class="bi bi-table"></i> Grade Sheet
                            </a>
                            <a href="{{ route('grading.configure', $class->id) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-gear"></i> Configure
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No grading classes yet. Click "Add Class" to get started.
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('grading.add-class') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if($availableSubjects->count() > 0)
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Select Class</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Choose a class...</option>
                                @foreach($availableSubjects as $subject)
                                    <option value="{{ $subject->id }}">
                                        {{ $subject->subject_name }} - {{ $subject->section }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="term" class="form-label">Term</label>
                            <select class="form-select" id="term" name="term" required>
                                <option value="">Select term...</option>
                                <option value="prelim">Prelim</option>
                                <option value="midterm">Midterm</option>
                                <option value="finals">Finals</option>
                            </select>
                        </div>

                        <div class="alert alert-info mb-0">
                            <small>
                                <strong>Note:</strong> After adding the class, you'll configure the grading components 
                                (activities, quizzes, exams, etc.) and their formulas.
                            </small>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            No available classes. Please ensure you have:
                            <ul class="mb-0 mt-2">
                                <li>Synced classes from Google Classroom</li>
                                <li>Imported students for your classes</li>
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if($availableSubjects->count() > 0)
                        <button type="submit" class="btn btn-primary">Add Class</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
