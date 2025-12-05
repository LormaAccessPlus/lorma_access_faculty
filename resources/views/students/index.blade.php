@extends('layouts.admin')

@section('title', 'Students')

@section('page-title', 'Students Management')

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span class="text-gray-900">Students</span>
    </li>
@endsection

@section('content')
<!-- Statistics Cards -->
@php
    $totalStudents = $subjects->sum(fn($s) => $s->studentMappings->count());
    $totalMatched = $subjects->sum(fn($s) => $s->studentMappings->where('auto_matched', true)->count());
    $totalSubjects = $subjects->count();
    $matchRate = $totalStudents > 0 ? round(($totalMatched / $totalStudents) * 100) : 0;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Students -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-2xl text-blue-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Students</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalStudents) }}</p>
            </div>
        </div>
    </div>

    <!-- Matched -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Matched</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalMatched) }}</p>
            </div>
        </div>
    </div>

    <!-- Subjects -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-book text-2xl text-purple-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Subjects</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalSubjects }}</p>
            </div>
        </div>
    </div>

    <!-- Match Rate -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-2xl text-yellow-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Match Rate</p>
                <p class="text-2xl font-bold text-gray-900">{{ $matchRate }}%</p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-1">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="searchInput" 
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Search by subject name or code...">
            </div>
        </div>
        <div>
            <select id="filterStatus" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Status</option>
                <option value="complete">Fully Matched</option>
                <option value="partial">Partially Matched</option>
                <option value="none">No Students</option>
            </select>
        </div>
        <div>
            <select id="sortBy" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="recent">Most Recent</option>
                <option value="name">Subject Name</option>
                <option value="students">Most Students</option>
                <option value="matched">Match Rate</option>
            </select>
        </div>
    </div>
</div>

<!-- Subjects Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="subjectsGrid">
    @forelse($subjects as $subject)
        @php
            $studentCount = $subject->studentMappings->count();
            $matchedCount = $subject->studentMappings->where('auto_matched', true)->count();
            $matchPercentage = $studentCount > 0 ? round(($matchedCount / $studentCount) * 100) : 0;
        @endphp
        <div class="subject-card bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" 
             data-name="{{ strtolower($subject->subject_name) }}"
             data-code="{{ strtolower($subject->subject_code) }}"
             data-students="{{ $studentCount }}"
             data-matched="{{ $matchPercentage }}"
             data-status="{{ $studentCount == 0 ? 'none' : ($matchPercentage == 100 ? 'complete' : 'partial') }}">
            <div class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $subject->subject_name }}</h3>
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-tag text-xs"></i> {{ $subject->subject_code }} - {{ $subject->section }}
                        </p>
                    </div>
                    @if($matchPercentage == 100 && $studentCount > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>
                        </span>
                    @elseif($studentCount > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                        </span>
                    @endif
                </div>
                
                <!-- Progress Bar -->
                @if($studentCount > 0)
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs text-gray-600">Match Progress</span>
                            <span class="text-xs font-semibold text-gray-900">{{ $matchedCount }}/{{ $studentCount }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $matchPercentage == 100 ? 'bg-green-500' : 'bg-yellow-500' }}" 
                                 style="width: {{ $matchPercentage }}%"></div>
                        </div>
                    </div>
                @endif
                
                <!-- Stats -->
                <div class="flex gap-2 mb-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-users mr-1"></i> {{ $studentCount }} Students
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> {{ $matchedCount }} Matched
                    </span>
                </div>

                <!-- GCR Connection Status -->
                @if($subject->gcr_class_id)
                    <div class="mb-3 p-2 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-xs text-green-700">
                            <i class="fas fa-check-circle mr-1"></i>
                            Connected to Google Classroom - Students auto-synced
                        </p>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex gap-2">
                    @if($subject->gcr_class_id)
                        <button type="button" 
                                onclick="syncStudentsFromGCR{{ $subject->id }}()"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <i class="fas fa-sync mr-2"></i> Sync Students from GCR
                        </button>
                    @else
                        <div class="flex-1 text-sm text-gray-500 italic">
                            Connect to Google Classroom to sync students
                        </div>
                    @endif

                    @if($studentCount > 0)
                        <button type="button"
                                onclick="openViewModal{{ $subject->id }}()"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <i class="fas fa-eye"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <script>
            function syncStudentsFromGCR{{ $subject->id }}() {
                if (!confirm('Sync students from Google Classroom? This will fetch all students from the connected course.')) {
                    return;
                }

                fetch('{{ route('classroom.sync-students') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        subject_id: {{ $subject->id }}
                    })
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
                    alert('Error syncing students: ' + error);
                });
            }
        </script>

        <!-- View Students Modal -->
        @if($studentCount > 0)
            <div id="viewModal{{ $subject->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeViewModal{{ $subject->id }}()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                        <div class="bg-blue-600 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-white">
                                        <i class="fas fa-users mr-2"></i>Students - {{ $subject->subject_name }}
                                    </h3>
                                    <p class="text-sm text-blue-100">{{ $subject->subject_code }} - {{ $subject->section }}</p>
                                </div>
                                <button type="button" onclick="closeViewModal{{ $subject->id }}()" class="text-white hover:text-gray-200">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>
                        <div class="bg-white px-6 py-4">
                            <!-- Stats Summary -->
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div class="bg-blue-50 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Total Students</span>
                                        <span class="text-xl font-bold text-blue-600">{{ $studentCount }}</span>
                                    </div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Matched</span>
                                        <span class="text-xl font-bold text-green-600">{{ $matchedCount }}</span>
                                    </div>
                                </div>
                                <div class="bg-yellow-50 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Not Matched</span>
                                        <span class="text-xl font-bold text-yellow-600">{{ $studentCount - $matchedCount }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Search within modal -->
                            <div class="mb-4">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" 
                                           onkeyup="filterStudents{{ $subject->id }}(this)"
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Search students by name or email...">
                                </div>
                            </div>

                            <!-- Students Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200" id="studentsTable{{ $subject->id }}">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-user mr-1"></i>Name
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-envelope mr-1"></i>Email
                                            </th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-check-circle mr-1"></i>Status
                                            </th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <i class="fas fa-chart-line mr-1"></i>Confidence
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($subject->studentMappings as $index => $mapping)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                            <span class="text-sm font-medium text-blue-600">{{ strtoupper(substr($mapping->student_name, 0, 1)) }}</span>
                                                        </div>
                                                        <div class="ml-3">
                                                            <p class="text-sm font-medium text-gray-900">{{ $mapping->student_name }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-500">
                                                    {{ $mapping->student_email ?? '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    @if($mapping->auto_matched)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-check-circle mr-1"></i>Matched
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            <i class="fas fa-exclamation-circle mr-1"></i>Not Matched
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    @if($mapping->mapping_confidence)
                                                        @php
                                                            $confidence = $mapping->mapping_confidence;
                                                            $badgeClass = $confidence >= 90 ? 'green' : ($confidence >= 70 ? 'yellow' : 'red');
                                                        @endphp
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $badgeClass }}-100 text-{{ $badgeClass }}-800">
                                                            {{ number_format($confidence, 0) }}%
                                                        </span>
                                                    @else
                                                        <span class="text-sm text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" onclick="closeViewModal{{ $subject->id }}()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-times mr-1"></i> Close
                            </button>
                            <button type="button" onclick="exportStudents{{ $subject->id }}()" class="px-4 py-2 border border-blue-600 rounded-lg text-sm font-medium text-blue-600 hover:bg-blue-50">
                                <i class="fas fa-download mr-1"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function openViewModal{{ $subject->id }}() {
                    document.getElementById('viewModal{{ $subject->id }}').classList.remove('hidden');
                }
                function closeViewModal{{ $subject->id }}() {
                    document.getElementById('viewModal{{ $subject->id }}').classList.add('hidden');
                }
                function filterStudents{{ $subject->id }}(input) {
                    const filter = input.value.toLowerCase();
                    const table = document.getElementById('studentsTable{{ $subject->id }}');
                    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                    
                    for (let i = 0; i < rows.length; i++) {
                        const nameCell = rows[i].getElementsByTagName('td')[1];
                        const emailCell = rows[i].getElementsByTagName('td')[2];
                        
                        if (nameCell && emailCell) {
                            const nameText = nameCell.textContent || nameCell.innerText;
                            const emailText = emailCell.textContent || emailCell.innerText;
                            
                            if (nameText.toLowerCase().indexOf(filter) > -1 || 
                                emailText.toLowerCase().indexOf(filter) > -1) {
                                rows[i].style.display = '';
                            } else {
                                rows[i].style.display = 'none';
                            }
                        }
                    }
                }
                function exportStudents{{ $subject->id }}() {
                    const table = document.getElementById('studentsTable{{ $subject->id }}');
                    let csv = [];
                    const rows = table.querySelectorAll('tr');
                    
                    // Headers
                    csv.push(['#', 'Name', 'Email', 'Status', 'Confidence'].join(','));
                    
                    // Data rows
                    const dataRows = table.querySelectorAll('tbody tr');
                    dataRows.forEach(row => {
                        if (row.style.display !== 'none') {
                            const cols = row.querySelectorAll('td');
                            const rowData = [];
                            cols.forEach(col => {
                                let text = col.innerText.replace(/,/g, ';').trim();
                                rowData.push(text);
                            });
                            csv.push(rowData.join(','));
                        }
                    });
                    
                    // Download
                    const csvContent = csv.join('\n');
                    const blob = new Blob([csvContent], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'students_{{ $subject->id }}_' + new Date().getTime() + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                }
            </script>
        @endif
    @empty
        <div class="col-span-full">
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="mb-4">
                    <i class="fas fa-inbox text-6xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Subjects Found</h3>
                <p class="text-gray-600 mb-6">
                    No subjects found for the current semester. Please sync your classes from Google Classroom first.
                </p>
                <a href="{{ route('subjects.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i> Go to Subjects
                </a>
            </div>
        </div>
    @endforelse
</div>


<script>
// Search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const sortBy = document.getElementById('sortBy');
    
    if (!searchInput || !filterStatus || !sortBy) return;
    
    function filterAndSort() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusFilter = filterStatus.value;
        const sortOption = sortBy.value;
        
        let cards = Array.from(document.querySelectorAll('.subject-card'));
        
        // Filter
        cards.forEach(card => {
            const name = card.dataset.name;
            const code = card.dataset.code;
            const status = card.dataset.status;
            
            const matchesSearch = name.includes(searchTerm) || code.includes(searchTerm);
            const matchesStatus = !statusFilter || status === statusFilter;
            
            card.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
        
        // Sort
        const visibleCards = cards.filter(card => card.style.display !== 'none');
        visibleCards.sort((a, b) => {
            switch(sortOption) {
                case 'name':
                    return a.dataset.name.localeCompare(b.dataset.name);
                case 'students':
                    return parseInt(b.dataset.students) - parseInt(a.dataset.students);
                case 'matched':
                    return parseInt(b.dataset.matched) - parseInt(a.dataset.matched);
                default: // recent
                    return 0;
            }
        });
        
        const grid = document.getElementById('subjectsGrid');
        visibleCards.forEach(card => grid.appendChild(card));
    }
    
    searchInput.addEventListener('input', filterAndSort);
    filterStatus.addEventListener('change', filterAndSort);
    sortBy.addEventListener('change', filterAndSort);
});
</script>

@endsection
