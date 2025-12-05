@extends('layouts.admin')

@section('title', 'Students')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">Students</h2>
            <p class="text-muted">Import and manage student lists with automatic Google Classroom matching</p>
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
        @forelse($subjects as $subject)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $subject->subject_name }}</h5>
                        <p class="card-text text-muted mb-2">
                            <small>{{ $subject->subject_code }} - {{ $subject->section }}</small>
                        </p>
                        
                        <div class="mb-3">
                            <span class="badge bg-primary">
                                {{ $subject->studentMappings->count() }} Students
                            </span>
                            <span class="badge bg-success">
                                {{ $subject->studentMappings->where('auto_matched', true)->count() }} Matched
                            </span>
                        </div>

                        <button type="button" class="btn btn-sm btn-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#uploadModal{{ $subject->id }}">
                            <i class="bi bi-upload"></i> Import CSV
                        </button>

                        @if($subject->studentMappings->count() > 0)
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#viewStudentsModal{{ $subject->id }}">
                                <i class="bi bi-eye"></i> View Students
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Upload Modal -->
            <div class="modal fade" id="uploadModal{{ $subject->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Import Students - {{ $subject->subject_name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('students.upload-csv') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                
                                <div class="alert alert-info">
                                    <strong>CSV Format:</strong> Your CSV should contain at least a <code>name</code> column. 
                                    An <code>email</code> column is recommended for better matching accuracy.
                                </div>

                                <div class="mb-3">
                                    <label for="csv_file{{ $subject->id }}" class="form-label">Select CSV File</label>
                                    <input type="file" class="form-control" id="csv_file{{ $subject->id }}" 
                                           name="csv_file" accept=".csv" required>
                                </div>

                                <div class="alert alert-warning mb-0">
                                    <small>
                                        <strong>Note:</strong> Students will be automatically matched with Google Classroom 
                                        based on name and email. High-confidence matches will be saved automatically.
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Upload & Match</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View Students Modal -->
            @if($subject->studentMappings->count() > 0)
                <div class="modal fade" id="viewStudentsModal{{ $subject->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Students - {{ $subject->subject_name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Confidence</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($subject->studentMappings as $mapping)
                                                <tr>
                                                    <td>{{ $mapping->student_name }}</td>
                                                    <td>{{ $mapping->student_email ?? '-' }}</td>
                                                    <td>
                                                        @if($mapping->auto_matched)
                                                            <span class="badge bg-success">Matched</span>
                                                        @else
                                                            <span class="badge bg-warning">Not Matched</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($mapping->mapping_confidence)
                                                            {{ number_format($mapping->mapping_confidence, 0) }}%
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No subjects found for the current semester. 
                    Please sync your classes from Google Classroom first.
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
