@extends('layouts.admin')

@section('page-title', 'Mapping Conflicts')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Resolve Mapping Conflicts</h1>
        <p class="text-gray-600 mt-2">
            Subject: {{ $subject->subject_code }} - {{ $subject->subject_name }} ({{ $subject->section }})
        </p>
    </div>

    @if(count($conflicts) === 0)
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                No conflicts found! All mappings look good.
            </div>
        </div>
        
        <div class="mt-6">
            <a href="{{ route('mappings.index', $subject) }}" 
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                Back to Mappings
            </a>
        </div>
    @else
        @foreach($conflicts as $index => $conflict)
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-red-700">
                        Conflict {{ $index + 1 }}: 
                        @if($conflict['reason'] === 'low_confidence')
                            Low Confidence Mapping
                        @elseif($conflict['reason'] === 'duplicate_school_student')
                            Duplicate School Student Assignment
                        @endif
                    </h2>
                </div>

                @if($conflict['reason'] === 'low_confidence')
                    <!-- Low Confidence Conflict -->
                    <div class="p-6">
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-4">
                                This mapping has low confidence ({{ number_format($conflict['confidence'] * 100, 1) }}%) and may need review.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="border rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">GCR Student</h4>
                                <div class="text-sm text-gray-600">
                                    <p><strong>Name:</strong> {{ $conflict['mapping']->student_name }}</p>
                                    <p><strong>Email:</strong> {{ $conflict['mapping']->student_email ?? 'N/A' }}</p>
                                </div>
                            </div>
                            
                            <div class="border rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">Current School Student Mapping</h4>
                                <div class="text-sm text-gray-600">
                                    @if($conflict['mapping']->school_student_id)
                                        <p><strong>ID:</strong> {{ $conflict['mapping']->school_student_id }}</p>
                                        <p><strong>Confidence:</strong> {{ number_format($conflict['confidence'] * 100, 1) }}%</p>
                                    @else
                                        <p class="text-red-600">Not mapped to any school student</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex space-x-3">
                            <button onclick="editMapping({{ $conflict['mapping']->id }})" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                Edit Mapping
                            </button>
                            <button onclick="deleteMapping({{ $conflict['mapping']->id }})" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                                Delete Mapping
                            </button>
                        </div>
                    </div>

                @elseif($conflict['reason'] === 'duplicate_school_student')
                    <!-- Duplicate School Student Conflict -->
                    <div class="p-6">
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Multiple GCR students are mapped to the same school student (ID: {{ $conflict['school_student_id'] }}).
                            </p>
                        </div>
                        
                        <div class="space-y-4">
                            @foreach($conflict['mappings'] as $mapping)
                                <div class="border rounded-lg p-4 flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $mapping->student_name }}</h4>
                                        <p class="text-sm text-gray-600">{{ $mapping->student_email ?? 'No email' }}</p>
                                        <p class="text-sm text-gray-500">
                                            Confidence: {{ number_format($mapping->mapping_confidence * 100, 1) }}%
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <input type="radio" 
                                               name="keep_mapping_{{ $conflict['school_student_id'] }}" 
                                               value="{{ $mapping->id }}"
                                               id="keep_{{ $mapping->id }}"
                                               class="text-blue-600">
                                        <label for="keep_{{ $mapping->id }}" class="text-sm text-gray-700">
                                            Keep this mapping
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6">
                            <button onclick="resolveDuplicateConflict({{ $conflict['school_student_id'] }})" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                Resolve Conflict
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="mt-6">
            <a href="{{ route('mappings.index', $subject) }}" 
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                Back to Mappings
            </a>
        </div>
    @endif
</div>

<!-- Edit Mapping Modal -->
<div id="editMappingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Student Mapping</h3>
            <form id="editMappingForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">School Student</label>
                    <select id="schoolStudentSelect" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Select a school student...</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentMappingId = null;

async function editMapping(mappingId) {
    currentMappingId = mappingId;
    
    // Load school students
    try {
        const response = await fetch(`{{ route('mappings.school-students', $subject) }}`);
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('schoolStudentSelect');
            select.innerHTML = '<option value="">Select a school student...</option>';
            
            data.students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.full_name} (${student.email})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load school students:', error);
    }
    
    document.getElementById('editMappingModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editMappingModal').classList.add('hidden');
    currentMappingId = null;
}

document.getElementById('editMappingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!currentMappingId) return;
    
    const schoolStudentId = document.getElementById('schoolStudentSelect').value;
    
    try {
        const response = await fetch(`{{ url('subjects') }}/{{ $subject->id }}/mappings/${currentMappingId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                school_student_id: schoolStudentId || null
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update mapping: ' + data.message);
        }
    } catch (error) {
        console.error('Failed to update mapping:', error);
        alert('Failed to update mapping');
    }
});

async function deleteMapping(mappingId) {
    if (!confirm('Are you sure you want to delete this mapping?')) {
        return;
    }
    
    try {
        const response = await fetch(`{{ url('subjects') }}/{{ $subject->id }}/mappings/${mappingId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete mapping: ' + data.message);
        }
    } catch (error) {
        console.error('Failed to delete mapping:', error);
        alert('Failed to delete mapping');
    }
}

async function resolveDuplicateConflict(schoolStudentId) {
    const selectedRadio = document.querySelector(`input[name="keep_mapping_${schoolStudentId}"]:checked`);
    
    if (!selectedRadio) {
        alert('Please select which mapping to keep.');
        return;
    }
    
    const keepMappingId = parseInt(selectedRadio.value);
    const allRadios = document.querySelectorAll(`input[name="keep_mapping_${schoolStudentId}"]`);
    const removeMappingIds = [];
    
    allRadios.forEach(radio => {
        if (parseInt(radio.value) !== keepMappingId) {
            removeMappingIds.push(parseInt(radio.value));
        }
    });
    
    try {
        const response = await fetch(`{{ route('mappings.resolve-conflict', $subject) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                keep_mapping_id: keepMappingId,
                remove_mapping_ids: removeMappingIds
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to resolve conflict: ' + data.message);
        }
    } catch (error) {
        console.error('Failed to resolve conflict:', error);
        alert('Failed to resolve conflict');
    }
}
</script>
@endpush
@endsection