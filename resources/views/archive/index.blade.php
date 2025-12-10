@extends('layouts.admin')

@section('page-title', 'Archived Subjects')

@section('breadcrumbs')
<li><i class="fas fa-chevron-right text-gray-400 mx-2"></i></li>
<li class="text-gray-900">Archived Subjects</li>
@endsection

@section('content')
<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-archive mr-2 text-yellow-600"></i>
                    Archived Subjects
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    View subjects that have been archived in Google Classroom. Grades are read-only but can still be exported.
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex gap-3">
                <button onclick="syncArchives()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Sync Archives
                </button>
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center px-3 py-2 border text-sm leading-4 font-medium rounded-md bg-white transition-colors"
                   style="border-color: #08695A; color: #08695A;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Bar -->
    <div class="px-6 py-3 bg-yellow-50">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <div class="flex items-center">
                <i class="fas fa-folder-open text-yellow-500 mr-2"></i>
                <span class="text-gray-600">Total Archived:</span>
                <span class="font-semibold text-gray-900 ml-1">{{ $archivedSubjects->count() }}</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                <span class="text-gray-600">Archived subjects are read-only</span>
            </div>
        </div>
    </div>
</div>


<!-- Archived Subjects List -->
@if($archivedSubjects->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i class="fas fa-archive text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Archived Subjects</h3>
            <p class="text-gray-500 max-w-md">
                You don't have any archived subjects yet. When a Google Classroom class is archived, 
                the connected subject will appear here.
            </p>
        </div>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($archivedSubjects as $subject)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <!-- Subject Header -->
                <div class="px-5 py-4 bg-gradient-to-r from-yellow-50 to-orange-50 border-b border-yellow-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $subject->subject_code }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $subject->subject_name }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-archive mr-1"></i>
                            Archived
                        </span>
                    </div>
                </div>
                
                <!-- Subject Info -->
                <div class="px-5 py-4">
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-users w-5 text-gray-400"></i>
                            <span class="ml-2">Section: <strong>{{ $subject->section }}</strong></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-calendar w-5 text-gray-400"></i>
                            <span class="ml-2">{{ $subject->academic_year }} - Semester {{ $subject->semester }}</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-user-graduate w-5 text-gray-400"></i>
                            <span class="ml-2">{{ $subject->studentMappings->count() }} Students</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-tasks w-5 text-gray-400"></i>
                            <span class="ml-2">{{ $subject->activities->count() }} Activities</span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('archive.show', $subject) }}" 
                           class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white transition-colors"
                           style="background-color: #08695A;"
                           onmouseover="this.style.backgroundColor='#065A4A';"
                           onmouseout="this.style.backgroundColor='#08695A';">
                            <i class="fas fa-eye mr-1.5"></i>
                            View Grades
                        </a>
                        <a href="{{ route('grades.export.full-matrix', $subject) }}?dean_name=" 
                           class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 transition-colors">
                            <i class="fas fa-file-pdf mr-1.5"></i>
                            Export
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<script>
async function syncArchives() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Syncing...';
    
    try {
        const response = await fetch('{{ route("archive.sync") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            if (data.archived_count > 0) {
                alert(`Success! ${data.archived_count} subject(s) have been archived and moved from other pages.`);
                // Reload the page to show newly archived subjects
                window.location.reload();
            } else {
                alert('Sync complete! No new archived subjects found.');
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to sync archives'));
        }
    } catch (error) {
        console.error('Sync error:', error);
        alert('Error: Failed to sync archives. Please try again.');
    } finally {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    }
}
</script>

@endsection
