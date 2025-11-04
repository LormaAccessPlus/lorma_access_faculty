@extends('layouts.admin')

@section('page-title', 'Student Mappings')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Student Mapping</h1>
        <p class="text-gray-600 mt-2">
            Subject: {{ $subject->subject_code }} - {{ $subject->subject_name }} ({{ $subject->section }})
        </p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900">Current Mappings</h2>
                <div class="space-x-2">
                    @if($subject->gcr_class_id)
                        <a href="{{ route('mappings.auto-match', $subject) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Auto Match Students
                        </a>
                    @endif
                    @if(count($conflicts) > 0)
                        <a href="{{ route('mappings.conflicts', $subject) }}" 
                           class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Resolve Conflicts ({{ count($conflicts) }})
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Student Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            School Student
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Confidence
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($mappings as $mapping)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $mapping->student_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ $mapping->student_email ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    @if($mapping->school_student_id)
                                        <span class="text-green-600">Mapped (ID: {{ $mapping->school_student_id }})</span>
                                    @else
                                        <span class="text-red-600">Not Mapped</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    @if($mapping->mapping_confidence >= 0.8)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ number_format($mapping->mapping_confidence * 100, 1) }}%
                                        </span>
                                    @elseif($mapping->mapping_confidence >= 0.5)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ number_format($mapping->mapping_confidence * 100, 1) }}%
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ number_format($mapping->mapping_confidence * 100, 1) }}%
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editMapping({{ $mapping->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    Edit
                                </button>
                                <button onclick="deleteMapping({{ $mapping->id }})" 
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No student mappings found. 
                                @if($subject->gcr_class_id)
                                    <a href="{{ route('mappings.auto-match', $subject) }}" class="text-blue-600 hover:text-blue-800">
                                        Start by auto-matching students
                                    </a>
                                @else
                                    Connect a Google Classroom first to import students.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
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
</script>
@endpush
@endsection