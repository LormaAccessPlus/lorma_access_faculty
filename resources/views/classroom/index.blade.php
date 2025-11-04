@extends('layouts.admin')

@section('page-title', 'Google Classroom')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Google Classroom Integration</h1>
            <button id="testConnection" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                Test Connection
            </button>
        </div>

        <!-- Connection Status -->
        <div id="connectionStatus" class="mb-6 p-4 rounded-lg hidden">
            <div class="flex items-center">
                <div id="statusIcon" class="mr-3"></div>
                <span id="statusMessage"></span>
            </div>
        </div>

        <!-- Connected Subjects -->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Connected Subjects</h2>
                <p class="text-gray-600 text-sm">Subjects that are connected to Google Classroom courses</p>
            </div>
            <div class="p-6">
                @if($connectedSubjects->count() > 0)
                    <div class="grid gap-4">
                        @foreach($connectedSubjects as $subject)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $subject->subject_code }}</h3>
                                        <p class="text-gray-600">{{ $subject->subject_name }}</p>
                                        <p class="text-sm text-gray-500">Section: {{ $subject->section }}</p>
                                        <p class="text-sm text-green-600 mt-2">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Connected to Google Classroom
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="syncStudents({{ $subject->id }})" 
                                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                            Sync Students
                                        </button>
                                        <button onclick="viewCourseDetails('{{ $subject->gcr_class_id }}')" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                            View Details
                                        </button>
                                        <button onclick="disconnectSubject({{ $subject->id }})" 
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                            Disconnect
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No subjects are currently connected to Google Classroom.</p>
                @endif
            </div>
        </div>

        <!-- Unconnected Subjects -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Available Subjects</h2>
                <p class="text-gray-600 text-sm">Connect these subjects to Google Classroom courses</p>
            </div>
            <div class="p-6">
                @if($unconnectedSubjects->count() > 0)
                    <div class="grid gap-4">
                        @foreach($unconnectedSubjects as $subject)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $subject->subject_code }}</h3>
                                        <p class="text-gray-600">{{ $subject->subject_name }}</p>
                                        <p class="text-sm text-gray-500">Section: {{ $subject->section }}</p>
                                        <p class="text-sm text-yellow-600 mt-2">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            Not connected to Google Classroom
                                        </p>
                                    </div>
                                    <div>
                                        <button onclick="showConnectModal({{ $subject->id }}, '{{ $subject->subject_code }}', '{{ $subject->subject_name }}')" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                            Connect to GCR
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">All subjects are connected to Google Classroom.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Connect Subject Modal -->
<div id="connectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Connect Subject to Google Classroom</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Subject: <span id="modalSubjectName" class="font-medium"></span>
                </p>
            </div>
            <div class="p-6 overflow-y-auto max-h-64">
                <div id="coursesLoading" class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-gray-600 mt-2">Loading Google Classroom courses...</p>
                </div>
                <div id="coursesList" class="hidden">
                    <p class="text-sm text-gray-600 mb-4">Select a Google Classroom course to connect:</p>
                    <div id="coursesContainer" class="space-y-2 max-h-48 overflow-y-auto">
                        <!-- Courses will be loaded here -->
                    </div>
                </div>
                <div id="coursesError" class="hidden text-center py-8">
                    <p class="text-red-600">Failed to load courses. Please try again.</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="closeConnectModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Course Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-96 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Google Classroom Course Details</h3>
            </div>
            <div class="p-6 overflow-y-auto max-h-80">
                <div id="detailsLoading" class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-gray-600 mt-2">Loading course details...</p>
                </div>
                <div id="detailsContent" class="hidden">
                    <!-- Course details will be loaded here -->
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeDetailsModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentSubjectId = null;

// Test Google Classroom connection
document.getElementById('testConnection').addEventListener('click', function() {
    const button = this;
    const originalText = button.textContent;
    button.textContent = 'Testing...';
    button.disabled = true;

    fetch('/classroom/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        showConnectionStatus(data.success, data.message);
    })
    .catch(error => {
        showConnectionStatus(false, 'Connection test failed');
    })
    .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
    });
});

function showConnectionStatus(success, message) {
    const statusDiv = document.getElementById('connectionStatus');
    const statusIcon = document.getElementById('statusIcon');
    const statusMessage = document.getElementById('statusMessage');

    statusDiv.className = `mb-6 p-4 rounded-lg ${success ? 'bg-green-100 border border-green-200' : 'bg-red-100 border border-red-200'}`;
    statusIcon.innerHTML = success ? '<i class="fas fa-check-circle text-green-600"></i>' : '<i class="fas fa-times-circle text-red-600"></i>';
    statusMessage.textContent = message;
    statusDiv.classList.remove('hidden');

    setTimeout(() => {
        statusDiv.classList.add('hidden');
    }, 5000);
}

function showConnectModal(subjectId, subjectCode, subjectName) {
    currentSubjectId = subjectId;
    document.getElementById('modalSubjectName').textContent = `${subjectCode} - ${subjectName}`;
    document.getElementById('connectModal').classList.remove('hidden');
    
    // Reset modal state
    document.getElementById('coursesLoading').classList.remove('hidden');
    document.getElementById('coursesList').classList.add('hidden');
    document.getElementById('coursesError').classList.add('hidden');
    
    // Fetch courses
    fetchGoogleClassroomCourses();
}

function closeConnectModal() {
    document.getElementById('connectModal').classList.add('hidden');
    currentSubjectId = null;
}

function fetchGoogleClassroomCourses() {
    fetch('/classroom/fetch-courses', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('coursesLoading').classList.add('hidden');
        
        if (data.success) {
            displayCourses(data.courses);
        } else {
            document.getElementById('coursesError').classList.remove('hidden');
        }
    })
    .catch(error => {
        document.getElementById('coursesLoading').classList.add('hidden');
        document.getElementById('coursesError').classList.remove('hidden');
    });
}

function displayCourses(courses) {
    const container = document.getElementById('coursesContainer');
    container.innerHTML = '';
    
    if (courses.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No courses found in Google Classroom.</p>';
    } else {
        courses.forEach(course => {
            const courseDiv = document.createElement('div');
            courseDiv.className = 'border border-gray-200 rounded p-3 hover:bg-gray-50 cursor-pointer';
            courseDiv.onclick = () => connectSubjectToCourse(course.id);
            
            courseDiv.innerHTML = `
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-medium text-gray-900">${course.name}</h4>
                        <p class="text-sm text-gray-600">${course.section || 'No section'}</p>
                        <p class="text-xs text-gray-500">Course ID: ${course.id}</p>
                    </div>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                        Connect
                    </button>
                </div>
            `;
            
            container.appendChild(courseDiv);
        });
    }
    
    document.getElementById('coursesList').classList.remove('hidden');
}

function connectSubjectToCourse(gcrClassId) {
    if (!currentSubjectId) return;
    
    fetch('/classroom/connect-subject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            subject_id: currentSubjectId,
            gcr_class_id: gcrClassId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeConnectModal();
            location.reload(); // Refresh page to show updated connections
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Failed to connect subject to course');
    });
}

function disconnectSubject(subjectId) {
    if (!confirm('Are you sure you want to disconnect this subject from Google Classroom? This will also remove all student mappings.')) {
        return;
    }
    
    fetch('/classroom/disconnect-subject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            subject_id: subjectId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Failed to disconnect subject');
    });
}

function syncStudents(subjectId) {
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Syncing...';
    button.disabled = true;
    
    fetch('/classroom/sync-students', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            subject_id: subjectId
        })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => {
        alert('Failed to sync students');
    })
    .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
    });
}

function viewCourseDetails(gcrClassId) {
    document.getElementById('detailsModal').classList.remove('hidden');
    document.getElementById('detailsLoading').classList.remove('hidden');
    document.getElementById('detailsContent').classList.add('hidden');
    
    fetch('/classroom/course-details', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            gcr_class_id: gcrClassId
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('detailsLoading').classList.add('hidden');
        
        if (data.success) {
            displayCourseDetails(data);
        } else {
            document.getElementById('detailsContent').innerHTML = '<p class="text-red-600">Failed to load course details.</p>';
        }
        
        document.getElementById('detailsContent').classList.remove('hidden');
    })
    .catch(error => {
        document.getElementById('detailsLoading').classList.add('hidden');
        document.getElementById('detailsContent').innerHTML = '<p class="text-red-600">Failed to load course details.</p>';
        document.getElementById('detailsContent').classList.remove('hidden');
    });
}

function displayCourseDetails(data) {
    const content = document.getElementById('detailsContent');
    const course = data.course;
    
    content.innerHTML = `
        <div class="space-y-4">
            <div>
                <h4 class="font-semibold text-gray-900">${course.name}</h4>
                <p class="text-gray-600">${course.section || 'No section'}</p>
                <p class="text-sm text-gray-500">Course ID: ${course.id}</p>
                ${course.description ? `<p class="text-sm text-gray-600 mt-2">${course.description}</p>` : ''}
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 p-3 rounded">
                    <h5 class="font-medium text-blue-900">Students</h5>
                    <p class="text-2xl font-bold text-blue-600">${data.students_count}</p>
                </div>
                <div class="bg-green-50 p-3 rounded">
                    <h5 class="font-medium text-green-900">Assignments</h5>
                    <p class="text-2xl font-bold text-green-600">${data.coursework_count}</p>
                </div>
            </div>
            
            ${course.alternate_link ? `
                <div>
                    <a href="${course.alternate_link}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        View in Google Classroom
                    </a>
                </div>
            ` : ''}
        </div>
    `;
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}
</script>
@endsection