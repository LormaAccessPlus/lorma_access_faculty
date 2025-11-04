@extends('layouts.admin')

@section('page-title', 'Grade Synchronization - ' . $subject->subject_code)

@section('breadcrumbs')
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('subjects.index') }}" class="text-gray-500 hover:text-gray-700">Subjects</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li><a href="{{ route('subjects.show', $subject) }}" class="text-gray-500 hover:text-gray-700">{{ $subject->subject_code }}</a></li>
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li class="text-gray-900">Grade Sync</li>
@endsection

@section('content')
<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">Grade Synchronization</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $subject->subject_code }} - {{ $subject->subject_name }}
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2" style="background-color: rgba(8, 105, 90, 0.1); color: #08695A;">
                        {{ $subject->section }}
                    </span>
                </p>
                @if(isset($academicYear) && isset($semester))
                    <p class="text-xs text-gray-500 mt-1">{{ $academicYear }} - Semester {{ $semester }}</p>
                @endif
            </div>
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <a href="{{ route('grades.matrix', $subject) }}" 
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #08695A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#08695A';">
                    <i class="fas fa-table mr-2"></i>
                    Grade Matrix
                </a>
                <a href="{{ route('grades.term', $subject) }}" 
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white transition-colors"
                   style="background-color: #0A7B6A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#0A7B6A';">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Term Grades
                </a>
                <a href="{{ route('subjects.show', $subject) }}" 
                   class="inline-flex items-center px-3 py-2 border text-sm leading-4 font-medium rounded-md bg-white transition-colors"
                   style="border-color: #08695A; color: #08695A;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Subject
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Database Connection Status -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-database text-gray-400 mr-3"></i>
                Application Database
            </h3>
        </div>
        <div class="px-6 py-4">
            @if(isset($connectionStatus['app_db']['connected']) && $connectionStatus['app_db']['connected'])
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">Connected Successfully</p>
                        <p class="text-xs text-green-600">Ready for grade operations</p>
                    </div>
                </div>
            @else
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-times text-red-600"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">Connection Failed</p>
                        @if(isset($connectionStatus['app_db']['error']))
                            <p class="text-xs text-red-600">{{ $connectionStatus['app_db']['error'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-school text-gray-400 mr-3"></i>
                School Database
            </h3>
        </div>
        <div class="px-6 py-4">
            @if(isset($connectionStatus['school_db']['connected']) && $connectionStatus['school_db']['connected'])
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">Connected Successfully</p>
                        <p class="text-xs text-green-600">Ready for synchronization</p>
                    </div>
                </div>
            @else
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-times text-red-600"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">Connection Failed</p>
                        @if(isset($connectionStatus['school_db']['error']))
                            <p class="text-xs text-red-600">{{ $connectionStatus['school_db']['error'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Synchronization Statistics -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Synchronization Status</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: rgba(8, 105, 90, 0.1);">
                    <i class="fas fa-users text-xl" style="color: #08695A;"></i>
                </div>
                <div class="text-2xl font-bold" style="color: #08695A;">{{ isset($stats['total_students']) ? $stats['total_students'] : 0 }}</div>
                <div class="text-sm text-gray-600">Total Students</div>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: rgba(10, 123, 106, 0.1);">
                    <i class="fas fa-calculator text-xl" style="color: #0A7B6A;"></i>
                </div>
                <div class="text-2xl font-bold" style="color: #0A7B6A;">{{ isset($stats['grades_computed']) ? $stats['grades_computed'] : 0 }}</div>
                <div class="text-sm text-gray-600">Grades Computed</div>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: rgba(6, 90, 74, 0.1);">
                    <i class="fas fa-sync-alt text-xl" style="color: #065A4A;"></i>
                </div>
                <div class="text-2xl font-bold" style="color: #065A4A;">{{ isset($stats['grades_synced']) ? $stats['grades_synced'] : 0 }}</div>
                <div class="text-sm text-gray-600">Grades Synced</div>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: rgba(12, 135, 118, 0.1);">
                    <i class="fas fa-clock text-xl" style="color: #0C8776;"></i>
                </div>
                <div class="text-2xl font-bold" style="color: #0C8776;">{{ isset($stats['pending_sync']) ? $stats['pending_sync'] : 0 }}</div>
                <div class="text-sm text-gray-600">Pending Sync</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span class="font-medium">Synchronization Progress</span>
                <span class="font-semibold">{{ isset($stats['sync_percentage']) ? $stats['sync_percentage'] : 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-300" 
                     style="width: {{ isset($stats['sync_percentage']) ? $stats['sync_percentage'] : 0 }}%; background: linear-gradient(90deg, #08695A, #0A7B6A);"></div>
            </div>
        </div>

        <!-- Sync Status -->
        @if(isset($verification['synchronized']) && $verification['synchronized'])
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">All grades are synchronized</p>
                        <p class="text-xs text-green-600">Your grades are up to date in the school database</p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-800">Synchronization issues detected</p>
                        <div class="mt-2 text-xs text-yellow-700 space-y-1">
                            @if(isset($verification['missing_in_school_db']) && count($verification['missing_in_school_db']) > 0)
                                <p>• {{ count($verification['missing_in_school_db']) }} grades missing in school database</p>
                            @endif
                            @if(isset($verification['discrepancies']) && count($verification['discrepancies']) > 0)
                                <p>• {{ count($verification['discrepancies']) }} grade discrepancies found</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Action Buttons -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Actions</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <button id="previewBtn" 
                    class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white transition-colors"
                    style="background-color: #08695A;"
                    onmouseover="this.style.backgroundColor='#065A4A';"
                    onmouseout="this.style.backgroundColor='#08695A';">
                <i class="fas fa-eye mr-2"></i>
                Preview Grades
            </button>
            
            <button id="verifyBtn" 
                    class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white transition-colors"
                    style="background-color: #0A7B6A;"
                    onmouseover="this.style.backgroundColor='#065A4A';"
                    onmouseout="this.style.backgroundColor='#0A7B6A';">
                <i class="fas fa-search mr-2"></i>
                Verify Sync Status
            </button>
            
            <button id="syncBtn" 
                    class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-lg text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background-color: #0C8776;"
                    onmouseover="if (!this.disabled) this.style.backgroundColor='#065A4A';"
                    onmouseout="if (!this.disabled) this.style.backgroundColor='#0C8776';"
                    {{ (isset($connectionStatus['school_db']['connected']) && !$connectionStatus['school_db']['connected']) ? 'disabled' : '' }}>
                <i class="fas fa-sync-alt mr-2"></i>
                Synchronize Grades
            </button>
            
            <button id="testConnectionsBtn" 
                    class="inline-flex items-center justify-center px-4 py-3 border text-sm font-medium rounded-lg bg-white transition-colors"
                    style="border-color: #08695A; color: #08695A;"
                    onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                    onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                <i class="fas fa-plug mr-2"></i>
                Test Connections
            </button>
        </div>
    </div>
</div>

    <!-- Results Area -->
    <div id="resultsArea" class="hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Results</h2>
            <div id="resultsContent"></div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Confirm Grade Synchronization</h3>
            <p class="text-gray-600 mb-4">
                This will synchronize all computed grades to the school database. This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button id="cancelSync" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button id="confirmSync" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                    Confirm Sync
                </button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectId = {{ $subject->id }};
    const academicYear = '{{ $academicYear }}';
    const semester = '{{ $semester }}';
    
    // Elements
    const previewBtn = document.getElementById('previewBtn');
    const verifyBtn = document.getElementById('verifyBtn');
    const syncBtn = document.getElementById('syncBtn');
    const testConnectionsBtn = document.getElementById('testConnectionsBtn');
    const resultsArea = document.getElementById('resultsArea');
    const resultsContent = document.getElementById('resultsContent');
    const confirmModal = document.getElementById('confirmModal');
    const cancelSync = document.getElementById('cancelSync');
    const confirmSync = document.getElementById('confirmSync');

    // Utility functions
    function showResults(content) {
        resultsContent.innerHTML = content;
        resultsArea.classList.remove('hidden');
        resultsArea.scrollIntoView({ behavior: 'smooth' });
    }

    function showLoading(button) {
        button.disabled = true;
        button.innerHTML = button.innerHTML.replace(/^.*?(<svg|$)/, 'Loading... $1');
    }

    function hideLoading(button, originalText) {
        button.disabled = false;
        button.innerHTML = originalText;
    }

    // Preview grades
    previewBtn.addEventListener('click', async function() {
        const originalText = this.innerHTML;
        showLoading(this);

        try {
            const response = await fetch(`/subjects/${subjectId}/grades/sync/preview`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    academic_year: academicYear,
                    semester: semester
                })
            });

            const data = await response.json();

            if (data.success) {
                let html = `<h3 class="text-lg font-semibold mb-4">Grade Preview (${data.data.count} records)</h3>`;
                
                if (data.data.count > 0) {
                    html += `
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prelim</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Midterm</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Finals</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Final Rating</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                    `;

                    data.data.grades.forEach(grade => {
                        html += `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${grade.student_name || 'N/A'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${grade.school_student_id || 'N/A'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${grade.prelim_grade || 'N/A'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${grade.midterm_grade || 'N/A'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${grade.finals_grade || 'N/A'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${grade.final_rating || 'N/A'}</td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    html += '<p class="text-gray-500">No grades available for synchronization.</p>';
                }

                showResults(html);
            } else {
                showResults(`<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">${data.message}</div>`);
            }
        } catch (error) {
            showResults(`<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">Error: ${error.message}</div>`);
        } finally {
            hideLoading(this, originalText);
        }
    });

    // Verify sync status
    verifyBtn.addEventListener('click', async function() {
        const originalText = this.innerHTML;
        showLoading(this);

        try {
            const response = await fetch(`/subjects/${subjectId}/grades/sync/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    academic_year: academicYear,
                    semester: semester
                })
            });

            const data = await response.json();

            if (data.success) {
                let html = '<h3 class="text-lg font-semibold mb-4">Verification Results</h3>';
                
                if (data.data.synchronized) {
                    html += '<div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 mb-4">All grades are properly synchronized!</div>';
                } else {
                    html += '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800 mb-4">Synchronization issues found:</div>';
                    
                    if (data.data.missing_in_school_db.length > 0) {
                        html += `<div class="mb-4"><h4 class="font-semibold mb-2">Missing in School Database (${data.data.missing_in_school_db.length}):</h4><ul class="list-disc list-inside">`;
                        data.data.missing_in_school_db.forEach(item => {
                            html += `<li>${item.student_name} (${item.school_student_id})</li>`;
                        });
                        html += '</ul></div>';
                    }
                    
                    if (data.data.discrepancies.length > 0) {
                        html += `<div class="mb-4"><h4 class="font-semibold mb-2">Grade Discrepancies (${data.data.discrepancies.length}):</h4><ul class="list-disc list-inside">`;
                        data.data.discrepancies.forEach(item => {
                            html += `<li>${item.student_name} (${item.school_student_id}) - ${item.differences.length} differences</li>`;
                        });
                        html += '</ul></div>';
                    }
                }

                html += `
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-blue-600">${data.data.app_db_count}</div>
                            <div class="text-sm text-gray-600">App Database</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-purple-600">${data.data.school_db_count}</div>
                            <div class="text-sm text-gray-600">School Database</div>
                        </div>
                    </div>
                `;

                showResults(html);
            } else {
                showResults(`<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">${data.message}</div>`);
            }
        } catch (error) {
            showResults(`<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">Error: ${error.message}</div>`);
        } finally {
            hideLoading(this, originalText);
        }
    });

    // Sync grades (with confirmation)
    syncBtn.addEventListener('click', function() {
        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
    });

    cancelSync.addEventListener('click', function() {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
    });

    confirmSync.addEventListener('click', async function() {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        
        const originalText = syncBtn.innerHTML;
        showLoading(syncBtn);

        try {
            const response = await fetch(`/subjects/${subjectId}/grades/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    academic_year: academicYear,
                    semester: semester,
                    confirm: true
                })
            });

            const data = await response.json();

            let html = '<h3 class="text-lg font-semibold mb-4">Synchronization Results</h3>';
            
            if (data.success) {
                html += `<div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 mb-4">${data.message}</div>`;
                html += `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-green-600">${data.data.stored_count}</div>
                            <div class="text-sm text-gray-600">Successfully Stored</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-red-600">${data.data.failed_count}</div>
                            <div class="text-sm text-gray-600">Failed</div>
                        </div>
                    </div>
                `;
                
                // Show success notification and refresh page
                showNotification(`Successfully synchronized ${data.data.stored_count} grade records!`, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                html += `<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800 mb-4">${data.message}</div>`;
                
                if (data.data.errors.length > 0) {
                    html += '<div class="mb-4"><h4 class="font-semibold mb-2">Errors:</h4><ul class="list-disc list-inside">';
                    data.data.errors.forEach(error => {
                        html += `<li>${typeof error === 'string' ? error : error.error}</li>`;
                    });
                    html += '</ul></div>';
                }
            }

            showResults(html);
        } catch (error) {
            showResults(`<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">Error: ${error.message}</div>`);
        } finally {
            hideLoading(syncBtn, originalText);
        }
    });

    // Test connections
    testConnectionsBtn.addEventListener('click', async function() {
        const originalText = this.innerHTML;
        showLoading(this);

        try {
            const response = await fetch('/grades/sync/test-connections', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                let html = '<h3 class="text-lg font-semibold mb-4">Connection Test Results</h3>';
                html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                
                // App DB
                html += `
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold mb-2 flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2 ${data.data.app_db.connected ? 'bg-green-500' : 'bg-red-500'}"></span>
                            Application Database
                        </h4>
                        <p class="${data.data.app_db.connected ? 'text-green-600' : 'text-red-600'}">
                            ${data.data.app_db.connected ? 'Connected' : 'Connection Failed'}
                        </p>
                        ${data.data.app_db.error ? `<p class="text-sm text-gray-500 mt-1">${data.data.app_db.error}</p>` : ''}
                    </div>
                `;
                
                // School DB
                html += `
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold mb-2 flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2 ${data.data.school_db.connected ? 'bg-green-500' : 'bg-red-500'}"></span>
                            School Database
                        </h4>
                        <p class="${data.data.school_db.connected ? 'text-green-600' : 'text-red-600'}">
                            ${data.data.school_db.connected ? 'Connected' : 'Connection Failed'}
                        </p>
                        ${data.data.school_db.error ? `<p class="text-sm text-gray-500 mt-1">${data.data.school_db.error}</p>` : ''}
                    </div>
                `;
                
                html += '</div>';
                showResults(html);
            } else {
                showResults(`<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">${data.message}</div>`);
            }
        } catch (error) {
            showResults(`<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">Error: ${error.message}</div>`);
        } finally {
            hideLoading(this, originalText);
        }
    });
});
</script>
@endsection
@endsection