@extends('layouts.admin')

@section('title', 'Grading')

@section('page-title', 'Grading Management')

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span class="text-gray-900">Grading</span>
    </li>
@endsection

@section('content')
<!-- Statistics Cards -->
@php
    $totalSubjects = $gradingClasses->groupBy('subject_id')->count();
    $totalComponents = $gradingClasses->sum(fn($c) => $c->components->count());
    $totalItems = $gradingClasses->sum(fn($c) => $c->components->sum(fn($comp) => $comp->items->count()));
    $groupedBySubject = $gradingClasses->groupBy('subject_id');
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Subjects -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chalkboard-teacher text-2xl text-blue-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Subjects</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalSubjects }}</p>
            </div>
        </div>
    </div>

    <!-- Components -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-puzzle-piece text-2xl text-purple-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Components</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalComponents }}</p>
            </div>
        </div>
    </div>

    <!-- Total Items -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-list-check text-2xl text-green-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Items</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalItems }}</p>
            </div>
        </div>
    </div>

    <!-- Available Subjects -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-book text-2xl text-yellow-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Available</p>
                <p class="text-2xl font-bold text-gray-900">{{ $availableSubjects->count() }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Bar -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="searchInput" 
                       class="block w-64 pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Search classes...">
            </div>
        </div>
        <button type="button" onclick="openAddClassModal()" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <i class="fas fa-plus-circle mr-2"></i> Add Class
        </button>
    </div>
</div>

<!-- Subjects Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="subjectsGrid">
    @forelse($groupedBySubject as $subjectId => $classes)
        @php
            $firstClass = $classes->first();
            $subject = $firstClass->subject;
            $totalTermComponents = $classes->sum(fn($c) => $c->components->count());
            $totalTermItems = $classes->sum(fn($c) => $c->components->sum(fn($comp) => $comp->items->count()));
            $configuredTerms = $classes->filter(fn($c) => $c->components->count() > 0)->count();
        @endphp
        
        <div class="subject-card bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" 
             data-name="{{ strtolower($subject->subject_name) }}"
             data-code="{{ strtolower($subject->subject_code) }}">
            <div class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $subject->subject_name }}</h3>
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-tag text-xs"></i> {{ $subject->subject_code }} - {{ $subject->section }}
                        </p>
                    </div>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="text-gray-400 hover:text-gray-600 p-2">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-10">
                            <button onclick="deleteSubjectClasses({{ $subjectId }})" 
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-trash mr-2"></i>Delete All Terms
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <div class="text-xs text-gray-600 mb-1">Terms</div>
                        <div class="text-lg font-bold text-blue-600">{{ $classes->count() }}</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-3 text-center">
                        <div class="text-xs text-gray-600 mb-1">Components</div>
                        <div class="text-lg font-bold text-purple-600">{{ $totalTermComponents }}</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <div class="text-xs text-gray-600 mb-1">Items</div>
                        <div class="text-lg font-bold text-green-600">{{ $totalTermItems }}</div>
                    </div>
                </div>

                <!-- Configuration Status -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-gray-600">Configuration</span>
                        <span class="text-xs font-semibold text-gray-900">{{ $configuredTerms }}/3 Terms</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $configuredTerms == 3 ? 'bg-green-500' : 'bg-yellow-500' }}" 
                             style="width: {{ ($configuredTerms / 3) * 100 }}%"></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    @php
                        $firstClass = $classes->where('term', 'prelim')->first() ?? $classes->first();
                    @endphp
                    <a href="{{ route('grading.grade-sheet', $firstClass->id) }}" 
                       class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-folder-open mr-2"></i> Open
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="mb-4">
                    <i class="fas fa-clipboard-list text-6xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Grading Classes Yet</h3>
                <p class="text-gray-600 mb-6">
                    Click "Add Class" to get started with your customizable grading sheets.
                </p>
                <button type="button" onclick="openAddClassModal()" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus-circle mr-2"></i> Add Your First Class
                </button>
            </div>
        </div>
    @endforelse
</div>


<!-- Add Class Modal -->
<div id="addClassModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeAddClassModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('grading.add-class') }}" method="POST">
                @csrf
                <div class="bg-blue-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            <i class="fas fa-plus-circle mr-2"></i>Add Grading Class
                        </h3>
                        <button type="button" onclick="closeAddClassModal()" class="text-white hover:text-gray-200">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="bg-white px-6 py-4">
                    @if($availableSubjects->count() > 0)
                        <div class="mb-4">
                            <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-book mr-1"></i> Select Class
                            </label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    id="subject_id" 
                                    name="subject_id" 
                                    required>
                                <option value="">Choose a class...</option>
                                @foreach($availableSubjects as $subject)
                                    <option value="{{ $subject->id }}">
                                        {{ $subject->subject_name }} - {{ $subject->section }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h4 class="text-sm font-semibold text-blue-900 mb-2">
                                <i class="fas fa-info-circle mr-1"></i> What Happens Next?
                            </h4>
                            <p class="text-sm text-blue-800 mb-2">
                                When you add this class, grading sheets will be automatically created for:
                            </p>
                            <ul class="text-sm text-blue-800 space-y-1 ml-5 list-disc">
                                <li><strong>Prelim</strong> - First term grading</li>
                                <li><strong>Midterm</strong> - Second term grading</li>
                                <li><strong>Finals</strong> - Third term grading</li>
                            </ul>
                            <p class="text-sm text-blue-800 mt-2">
                                You'll configure each term's grading components using tabs.
                            </p>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-yellow-900 mb-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i> No Available Classes
                            </h4>
                            <p class="text-sm text-yellow-800 mb-2">Please ensure you have:</p>
                            <ul class="text-sm text-yellow-800 space-y-1 ml-5 list-disc">
                                <li>Synced classes from Google Classroom</li>
                                <li>Imported students for your classes</li>
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" onclick="closeAddClassModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    @if($availableSubjects->count() > 0)
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus mr-1"></i> Add Class
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openAddClassModal() {
    document.getElementById('addClassModal').classList.remove('hidden');
}

function closeAddClassModal() {
    document.getElementById('addClassModal').classList.add('hidden');
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.subject-card');
            
            cards.forEach(card => {
                const name = card.dataset.name;
                const code = card.dataset.code;
                
                if (name.includes(searchTerm) || code.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});

// Delete all terms for a subject
function deleteSubjectClasses(subjectId) {
    if (confirm('Are you sure you want to delete all grading classes (Prelim, Midterm, Finals) for this subject? This will also delete all components, items, and grades.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/grading/delete-subject/' + subjectId;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        form.appendChild(csrfInput);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

@endsection
