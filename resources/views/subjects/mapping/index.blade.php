@extends('layouts.admin')

@section('title', 'Subject Mapping')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Subject Mapping</h1>
                <p class="text-gray-600">Match your School Database subjects with Google Classroom courses</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-500">
                    Academic Year: <span class="font-medium">{{ $currentAcademicYear }}</span> • 
                    Semester: <span class="font-medium">{{ $currentSemester }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <i class="fas fa-database text-white text-sm"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">School Subjects</p>
                    <p class="text-lg font-semibold text-gray-900">{{ count($availableSchoolSubjects) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                    <i class="fab fa-google text-white text-sm"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">GCR Courses</p>
                    <p class="text-lg font-semibold text-gray-900">{{ count($availableGcrCourses) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                    <i class="fas fa-link text-white text-sm"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Mapped Subjects</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['total_subjects'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                    <i class="fas fa-users text-white text-sm"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Student Mappings</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['total_student_mappings'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Mapping Interface -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- School Database Subjects -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-database text-blue-600 mr-2"></i>
                    Your School Database Subjects
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ count($availableSchoolSubjects) }} subjects available for mapping</p>
            </div>
            
            <div class="p-6">
                @if(count($availableSchoolSubjects) > 0)
                    <div class="space-y-3" id="schoolSubjects">
                        @foreach($availableSchoolSubjects as $subject)
                            <div class="school-subject border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-colors"
                                 data-subject-code="{{ $subject['subject_code'] }}"
                                 data-subject-name="{{ $subject['subject_name'] }}"
                                 data-section="{{ $subject['section'] }}"
                                 data-schedule-code="{{ $subject['schedule_code'] }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900">{{ $subject['subject_name'] }}</h3>
                                        <p class="text-sm text-gray-600">{{ $subject['subject_code'] }} • Section: {{ $subject['section'] }}</p>
                                    </div>
                                    <div class="text-blue-600">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-database text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No School Subjects Found</h3>
                        <p class="text-gray-600">No subjects available for the current academic period.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Google Classroom Courses -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fab fa-google text-green-600 mr-2"></i>
                    Your Google Classroom Courses
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ count($availableGcrCourses) }} courses available for mapping</p>
            </div>
            
            <div class="p-6">
                @if(count($availableGcrCourses) > 0)
                    <div class="space-y-3" id="gcrCourses">
                        @foreach($availableGcrCourses as $course)
                            <div class="gcr-course border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-green-50 hover:border-green-300 transition-colors"
                                 data-course-id="{{ $course['id'] }}"
                                 data-course-name="{{ $course['name'] }}"
                                 data-section="{{ $course['section'] ?? 'N/A' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900">{{ $course['name'] }}</h3>
                                        <p class="text-sm text-gray-600">
                                            ID: {{ $course['id'] }}
                                            @if($course['section'])
                                                • Section: {{ $course['section'] }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-green-600">
                                        <i class="fas fa-arrow-left"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-google text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Google Classroom Courses</h3>
                        <p class="text-gray-600">No courses available for mapping.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Mapping Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600 mt-1"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">How to create mappings:</h3>
                <p class="text-sm text-blue-700 mt-1">
                    1. Click on a <strong>School Database subject</strong> (left side) to select it<br>
                    2. Then click on the corresponding <strong>Google Classroom course</strong> (right side)<br>
                    3. Confirm the mapping to create a new managed subject
                </p>
            </div>
        </div>
    </div>

    <!-- Existing Mappings -->
    @if($existingMappings->count() > 0)
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Your Mapped Subjects</h2>
        </div>
        
        <div class="p-6">
            <div class="space-y-4">
                @foreach($existingMappings as $mapping)
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $mapping->subject_name }}</h3>
                                        <p class="text-sm text-gray-600">{{ $mapping->section }} • {{ $mapping->academic_year }} - Semester {{ $mapping->semester }}</p>
                                    </div>
                                    
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ $mapping->student_mappings_count }}</div>
                                        <div class="text-xs text-gray-500">Students</div>
                                    </div>
                                    
                                    <div class="text-center">
                                        @if($mapping->mapping_status === 'completed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>Complete
                                            </span>
                                        @elseif($mapping->mapping_status === 'pending_student_mapping')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i>Pending
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>Review
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                    <span><i class="fab fa-google mr-1"></i>{{ $mapping->gcr_class_name }}</span>
                                    <span><i class="fas fa-database mr-1"></i>{{ $mapping->school_subject_code }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2 ml-4">
                                <a href="{{ route('subjects.show', $mapping) }}" 
                                   class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                                
                                <button onclick="deleteMapping({{ $mapping->id }})" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Mapping Confirmation Modal -->
<div id="mappingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Confirm Subject Mapping</h3>
            <button id="closeMappingModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-6">
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h4 class="font-medium text-gray-900 mb-2">School Database Subject</h4>
                <div id="selectedSchoolSubject" class="text-sm text-gray-600"></div>
            </div>
            
            <div class="flex justify-center mb-4">
                <div class="bg-blue-100 rounded-full p-2">
                    <i class="fas fa-link text-blue-600"></i>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">Google Classroom Course</h4>
                <div id="selectedGcrCourse" class="text-sm text-gray-600"></div>
            </div>
        </div>
        
        <form id="createMappingForm">
            @csrf
            <input type="hidden" name="gcr_class_id" id="mappingGcrClassId">
            <input type="hidden" name="school_subject_code" id="mappingSchoolSubjectCode">
            <input type="hidden" name="academic_year" value="{{ $currentAcademicYear }}">
            <input type="hidden" name="semester" value="{{ $currentSemester }}">
            
            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2" 
                          placeholder="Add any notes about this mapping..."></textarea>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-end space-x-4">
                <button type="button" id="cancelMapping" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-link mr-2"></i>Create Mapping
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mapping JavaScript loaded');
    
    let selectedSchoolSubject = null;
    let selectedGcrCourse = null;
    
    const mappingModal = document.getElementById('mappingModal');
    const closeMappingModal = document.getElementById('closeMappingModal');
    const cancelMapping = document.getElementById('cancelMapping');
    const createMappingForm = document.getElementById('createMappingForm');
    
    // Debug: Check if elements exist
    const schoolSubjects = document.querySelectorAll('.school-subject');
    const gcrCourses = document.querySelectorAll('.gcr-course');
    
    console.log('School subjects found:', schoolSubjects.length);
    console.log('GCR courses found:', gcrCourses.length);
    console.log('Modal element:', mappingModal);
    
    // List all found elements
    schoolSubjects.forEach((el, index) => {
        console.log(`School subject ${index}:`, el.dataset.subjectName, el);
    });
    
    gcrCourses.forEach((el, index) => {
        console.log(`GCR course ${index}:`, el.dataset.courseName, el);
    });
    
    // Handle school subject selection
    document.querySelectorAll('.school-subject').forEach(element => {
        console.log('Adding click handler to school subject:', element.dataset.subjectName);
        element.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('School subject clicked:', this.dataset.subjectName);
            alert('School subject clicked: ' + this.dataset.subjectName);
            
            // Remove previous selection
            document.querySelectorAll('.school-subject').forEach(el => {
                el.classList.remove('bg-blue-100', 'border-blue-500');
                el.classList.add('border-gray-200');
            });
            
            // Add selection styling
            this.classList.add('bg-blue-100', 'border-blue-500');
            this.classList.remove('border-gray-200');
            
            // Store selection
            selectedSchoolSubject = {
                code: this.dataset.subjectCode,
                name: this.dataset.subjectName,
                section: this.dataset.section,
                scheduleCode: this.dataset.scheduleCode
            };
            
            console.log('Selected school subject:', selectedSchoolSubject);
            
            // Check if we can show modal
            checkForCompleteSelection();
        });
    });
    
    // Handle GCR course selection
    document.querySelectorAll('.gcr-course').forEach(element => {
        console.log('Adding click handler to GCR course:', element.dataset.courseName);
        element.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('GCR course clicked:', this.dataset.courseName);
            alert('GCR course clicked: ' + this.dataset.courseName);
            
            // Remove previous selection
            document.querySelectorAll('.gcr-course').forEach(el => {
                el.classList.remove('bg-green-100', 'border-green-500');
                el.classList.add('border-gray-200');
            });
            
            // Add selection styling
            this.classList.add('bg-green-100', 'border-green-500');
            this.classList.remove('border-gray-200');
            
            // Store selection
            selectedGcrCourse = {
                id: this.dataset.courseId,
                name: this.dataset.courseName,
                section: this.dataset.section
            };
            
            console.log('Selected GCR course:', selectedGcrCourse);
            
            // Check if we can show modal
            checkForCompleteSelection();
        });
    });
    
    // Check if both selections are made
    function checkForCompleteSelection() {
        console.log('Checking selections:', {
            schoolSubject: selectedSchoolSubject,
            gcrCourse: selectedGcrCourse
        });
        
        if (selectedSchoolSubject && selectedGcrCourse) {
            console.log('Both selections made, showing modal');
            showMappingModal();
        } else {
            console.log('Waiting for both selections...');
        }
    }
    
    // Show mapping confirmation modal
    function showMappingModal() {
        // Populate modal with selection details
        document.getElementById('selectedSchoolSubject').innerHTML = `
            <strong>${selectedSchoolSubject.name}</strong><br>
            Code: ${selectedSchoolSubject.code} • Section: ${selectedSchoolSubject.section}
        `;
        
        document.getElementById('selectedGcrCourse').innerHTML = `
            <strong>${selectedGcrCourse.name}</strong><br>
            ID: ${selectedGcrCourse.id} • Section: ${selectedGcrCourse.section}
        `;
        
        // Set hidden form fields
        document.getElementById('mappingGcrClassId').value = selectedGcrCourse.id;
        document.getElementById('mappingSchoolSubjectCode').value = selectedSchoolSubject.code;
        
        // Show modal
        mappingModal.classList.remove('hidden');
        mappingModal.classList.add('flex');
    }
    
    // Hide modal
    function hideModal() {
        mappingModal.classList.add('hidden');
        mappingModal.classList.remove('flex');
        
        // Clear selections
        clearSelections();
    }
    
    // Clear all selections
    function clearSelections() {
        selectedSchoolSubject = null;
        selectedGcrCourse = null;
        
        // Remove selection styling
        document.querySelectorAll('.school-subject').forEach(el => {
            el.classList.remove('bg-blue-100', 'border-blue-500');
            el.classList.add('border-gray-200');
        });
        
        document.querySelectorAll('.gcr-course').forEach(el => {
            el.classList.remove('bg-green-100', 'border-green-500');
            el.classList.add('border-gray-200');
        });
    }
    
    closeMappingModal.addEventListener('click', hideModal);
    cancelMapping.addEventListener('click', hideModal);
    
    // Handle form submission
    createMappingForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
        
        try {
            const response = await fetch('{{ route("subjects.mapping.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
                hideModal();
                
                // Redirect to subject view or reload page
                if (data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1500);
                } else {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            showNotification('An error occurred while creating the mapping', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});

// Delete mapping function
async function deleteMapping(subjectId) {
    if (!confirm('Are you sure you want to delete this subject mapping? This will also delete all student mappings.')) {
        return;
    }
    
    try {
        const response = await fetch(`/subjects/mapping/${subjectId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while deleting the mapping', 'error');
    }
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full max-w-sm`;
    
    const bgColor = {
        'success': 'bg-green-50 border border-green-200 text-green-800',
        'error': 'bg-red-50 border border-red-200 text-red-800',
        'warning': 'bg-yellow-50 border border-yellow-200 text-yellow-800',
        'info': 'bg-blue-50 border border-blue-200 text-blue-800'
    }[type] || 'bg-gray-50 border border-gray-200 text-gray-800';
    
    notification.className += ` ${bgColor}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
</script>
@endpush
@endsection