@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<!-- Welcome Section -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ $faculty->name }}!</h1>
    <p class="mt-1 text-sm text-gray-600">Here's what's happening with your courses today.</p>
</div>

<!-- School Database Link Warning -->
@if(!$faculty->school_faculty_id)
<div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-yellow-800">Account Not Linked to School Database</h3>
            <div class="mt-2 text-sm text-yellow-700">
                <p>To sync subjects and students from the school database, you need to link your account to a teacher record.</p>
            </div>
            <div class="mt-4">
                <a href="{{ route('faculty.link') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    Link Account Now
                    <svg class="ml-2 -mr-0.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Main Navigation Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <!-- Subjects -->
    <a href="{{ route('subjects.index') }}" class="text-white rounded-lg p-6 transition-all duration-200 transform hover:scale-105 h-32 flex items-center"
       style="background: linear-gradient(135deg, #08695A, #0A7B6A);"
       onmouseover="this.style.background='linear-gradient(135deg, #065A4A, #08695A)';"
       onmouseout="this.style.background='linear-gradient(135deg, #08695A, #0A7B6A)';">
        <div class="flex items-center justify-between w-full">
            <div>
                <h3 class="text-lg font-semibold">Subjects</h3>
                <p class="text-sm" style="color: rgba(255, 255, 255, 0.8);">Manage courses</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['total_subjects'] }}</p>
            </div>
            <i class="fas fa-book text-3xl" style="color: rgba(255, 255, 255, 0.7);"></i>
        </div>
    </a>

    <!-- Google Classroom -->
    <a href="{{ route('classroom.index') }}" class="text-white rounded-lg p-6 transition-all duration-200 transform hover:scale-105 h-32 flex items-center"
       style="background: linear-gradient(135deg, #0A7B6A, #0C8776);"
       onmouseover="this.style.background='linear-gradient(135deg, #065A4A, #0A7B6A)';"
       onmouseout="this.style.background='linear-gradient(135deg, #0A7B6A, #0C8776)';">
        <div class="flex items-center justify-between w-full">
            <div>
                <h3 class="text-lg font-semibold">Classroom</h3>
                <p class="text-sm" style="color: rgba(255, 255, 255, 0.8);">GCR Integration</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['connected_subjects'] }}/{{ $stats['total_subjects'] }}</p>
            </div>
            <i class="fab fa-google text-3xl" style="color: rgba(255, 255, 255, 0.7);"></i>
        </div>
    </a>

    <!-- Activities -->
    <a href="{{ route('activities.index') }}" class="text-white rounded-lg p-6 transition-all duration-200 transform hover:scale-105 h-32 flex items-center"
       style="background: linear-gradient(135deg, #0C8776, #0E9B87);"
       onmouseover="this.style.background='linear-gradient(135deg, #0A7B6A, #0C8776)';"
       onmouseout="this.style.background='linear-gradient(135deg, #0C8776, #0E9B87)';">
        <div class="flex items-center justify-between w-full">
            <div>
                <h3 class="text-lg font-semibold">Activities</h3>
                <p class="text-sm" style="color: rgba(255, 255, 255, 0.8);">Assignments & Exams</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['total_activities'] }}</p>
            </div>
            <i class="fas fa-tasks text-3xl" style="color: rgba(255, 255, 255, 0.7);"></i>
        </div>
    </a>

    <!-- Student Mapping -->
    <div class="text-white rounded-lg p-6 transition-all duration-200 transform hover:scale-105 h-32"
         style="background: linear-gradient(135deg, #0E9B87, #10B398);"
         onmouseover="this.style.background='linear-gradient(135deg, #0C8776, #0E9B87)';"
         onmouseout="this.style.background='linear-gradient(135deg, #0E9B87, #10B398)';">
        <div class="flex items-center justify-between h-full">
            <div class="flex flex-col justify-center">
                <h3 class="text-lg font-semibold">Students</h3>
                <p class="text-sm" style="color: rgba(255, 255, 255, 0.8);">Mapping Status</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['mapped_students'] }}/{{ $stats['total_mappings'] }}</p>
            </div>
            <i class="fas fa-users text-3xl" style="color: rgba(255, 255, 255, 0.7);"></i>
        </div>
    </div>

    <!-- Grade Matrix -->
    <div class="text-white rounded-lg p-6 transition-all duration-200 transform hover:scale-105 h-32"
         style="background: linear-gradient(135deg, #10B398, #12C5A9);"
         onmouseover="this.style.background='linear-gradient(135deg, #0E9B87, #10B398)';"
         onmouseout="this.style.background='linear-gradient(135deg, #10B398, #12C5A9)';">
        <div class="flex items-center justify-between h-full">
            <div class="flex flex-col justify-center">
                <h3 class="text-lg font-semibold">Grades</h3>
                <p class="text-sm" style="color: rgba(255, 255, 255, 0.8);">Grade Matrix</p>
                <p class="text-2xl font-bold mt-2">
                    @php
                        $totalGradableActivities = $stats['total_activities'];
                        $gradeProgress = $totalGradableActivities > 0 ? '✓' : '—';
                    @endphp
                    {{ $gradeProgress }}
                </p>
            </div>
            <i class="fas fa-table text-3xl" style="color: rgba(255, 255, 255, 0.7);"></i>
        </div>
    </div>

    <!-- Grade Synchronization -->
    <div class="text-white rounded-lg p-6 transition-all duration-200 transform hover:scale-105 h-32"
         style="background: linear-gradient(135deg, #12C5A9, #14D7BA);"
         onmouseover="this.style.background='linear-gradient(135deg, #10B398, #12C5A9)';"
         onmouseout="this.style.background='linear-gradient(135deg, #12C5A9, #14D7BA)';">
        <div class="flex items-center justify-between h-full">
            <div class="flex flex-col justify-center">
                <h3 class="text-lg font-semibold">Sync</h3>
                <p class="text-sm" style="color: rgba(255, 255, 255, 0.8);">Grade Sync</p>
                <p class="text-2xl font-bold mt-2">
                    @php
                        $syncStatus = $stats['total_subjects'] > 0 ? '🔄' : '—';
                    @endphp
                    {{ $syncStatus }}
                </p>
            </div>
            <i class="fas fa-sync-alt text-3xl" style="color: rgba(255, 255, 255, 0.7);"></i>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <!-- Total Subjects -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                        <i class="fas fa-book text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Subjects</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['total_subjects'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Activities -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                        <i class="fas fa-tasks text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Activities</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['total_activities'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- GCR Connected -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                        <i class="fab fa-google text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">GCR Connected</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['connected_subjects'] }}/{{ $stats['total_subjects'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Mappings -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                        <i class="fas fa-users text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Mapped Students</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['mapped_students'] }}/{{ $stats['total_mappings'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Indicators -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- GCR Connection Progress -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Google Classroom Integration</h3>
            <span class="text-sm font-medium text-gray-500">{{ $stats['gcr_connection_rate'] }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $stats['gcr_connection_rate'] }}%"></div>
        </div>
        <p class="mt-2 text-sm text-gray-600">{{ $stats['connected_subjects'] }} of {{ $stats['total_subjects'] }} subjects connected</p>
    </div>

    <!-- Student Mapping Progress -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Student Mapping Progress</h3>
            <span class="text-sm font-medium text-gray-500">{{ $stats['mapping_completion_rate'] }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-orange-600 h-2 rounded-full" style="width: {{ $stats['mapping_completion_rate'] }}%"></div>
        </div>
        <p class="mt-2 text-sm text-gray-600">{{ $stats['mapped_students'] }} of {{ $stats['total_mappings'] }} students mapped</p>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Subjects -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Recent Subjects</h3>
                    <a href="{{ route('subjects.index') }}" class="text-sm text-blue-600 hover:text-blue-500">View all</a>
                </div>
            </div>
            <div class="p-6">
                @if($recentSubjects->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentSubjects as $subject)
                        <div class="border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h4 class="text-sm font-medium text-gray-900">{{ $subject->subject_code }}</h4>
                                            @if($subject->gcr_class_id)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fab fa-google mr-1"></i>
                                                    Connected
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-600">{{ $subject->subject_name }}</p>
                                        <p class="text-xs text-gray-500">Section: {{ $subject->section }} | {{ $subject->activities->count() }} activities | {{ $subject->studentMappings->count() }} students</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('subjects.show', $subject) }}" class="text-blue-600 hover:text-blue-500" title="View Subject">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('subjects.edit', $subject) }}" class="text-gray-600 hover:text-gray-500" title="Edit Subject">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('grades.matrix', $subject) }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200">
                                        <i class="fas fa-table mr-1"></i>
                                        Grade Matrix
                                    </a>
                                    <a href="{{ route('grades.term', $subject) }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        Term Grades
                                    </a>
                                    <a href="{{ route('mappings.index', $subject) }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200">
                                        <i class="fas fa-users mr-1"></i>
                                        Students ({{ $subject->studentMappings->count() }})
                                    </a>
                                    @if($subject->activities->count() > 0)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-green-700 bg-green-100">
                                            <i class="fas fa-tasks mr-1"></i>
                                            {{ $subject->activities->count() }} Activities
                                        </span>
                                    @else
                                        <a href="{{ route('activities.create') }}?subject_id={{ $subject->id }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200">
                                            <i class="fas fa-plus mr-1"></i>
                                            Add Activities
                                        </a>
                                    @endif
                                    @if(!$subject->gcr_class_id)
                                        <a href="{{ route('classroom.index') }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200">
                                            <i class="fab fa-google mr-1"></i>
                                            Connect GCR
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6 space-y-3">
                
                <a href="{{ route('classroom.index') }}" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fab fa-google mr-2"></i>
                    Sync Classroom
                </a>
                <a href="{{ route('activities.index') }}" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-tasks mr-2"></i>
                    Manage Activities
                </a>
            </div>
        </div>

        <!-- Module Navigation -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">System Modules</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-3">
                    <!-- Subject Management -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-book text-white text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Subject Management</h4>
                                    <p class="text-xs text-gray-500">Manage your subjects and courses</p>
                                </div>
                            </div>
                            <div class="flex space-x-1">
                                <a href="{{ route('subjects.index') }}" class="text-blue-600 hover:text-blue-500 text-xs" title="View All">
                                    <i class="fas fa-list"></i>
                                </a>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Google Classroom Integration -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <i class="fab fa-google text-white text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Google Classroom</h4>
                                    <p class="text-xs text-gray-500">Sync with Google Classroom</p>
                                </div>
                            </div>
                            <div class="flex space-x-1">
                                <a href="{{ route('classroom.index') }}" class="text-purple-600 hover:text-purple-500 text-xs" title="Manage Integration">
                                    <i class="fas fa-sync"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Management -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-tasks text-white text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Activities</h4>
                                    <p class="text-xs text-gray-500">Manage assignments and exams</p>
                                </div>
                            </div>
                            <div class="flex space-x-1">
                                <a href="{{ route('activities.index') }}" class="text-green-600 hover:text-green-500 text-xs" title="View All">
                                    <i class="fas fa-list"></i>
                                </a>
                                <a href="{{ route('activities.create') }}" class="text-green-600 hover:text-green-500 text-xs" title="Create New">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Student Mapping -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-users text-white text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Student Mapping</h4>
                                    <p class="text-xs text-gray-500">Map GCR to school students</p>
                                </div>
                            </div>
                            <div class="flex space-x-1">
                                @if($recentSubjects->count() > 0)
                                    <a href="{{ route('mappings.index', $recentSubjects->first()) }}" class="text-orange-600 hover:text-orange-500 text-xs" title="Manage Mappings">
                                        <i class="fas fa-link"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Grade Matrix -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-table text-white text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Grade Matrix</h4>
                                    <p class="text-xs text-gray-500">Input and manage grades</p>
                                </div>
                            </div>
                            <div class="flex space-x-1">
                                @if($recentSubjects->count() > 0)
                                    <a href="{{ route('grades.matrix', $recentSubjects->first()) }}" class="text-red-600 hover:text-red-500 text-xs" title="Grade Matrix">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Grade Synchronization -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-sync-alt text-white text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Grade Synchronization</h4>
                                    <p class="text-xs text-gray-500">Sync grades to school database</p>
                                </div>
                            </div>
                            <div class="flex space-x-1">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Activities</h3>
            </div>
            <div class="p-6">
                @if($recentActivities->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentActivities as $activity)
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-{{ $activity->type === 'lecture' ? 'blue' : 'purple' }}-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-{{ $activity->type === 'lecture' ? 'chalkboard-teacher' : 'flask' }} text-{{ $activity->type === 'lecture' ? 'blue' : 'purple' }}-600 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $activity->name }}</p>
                                <p class="text-xs text-gray-500">{{ $activity->subject->subject_code }} • {{ ucfirst($activity->term) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-4">No activities created yet</p>
                @endif
            </div>
        </div>

        <!-- Getting Started Workflow -->
        @if($stats['total_subjects'] == 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <i class="fas fa-rocket text-blue-500 mr-2"></i>
                    Getting Started
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold">2</div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-gray-900">Connect Google Classroom</h4>
                            <p class="text-xs text-gray-600 mt-1">Sync with your Google Classroom courses</p>
                            <a href="{{ route('classroom.index') }}" class="text-xs text-purple-600 hover:text-purple-500 font-medium mt-1 inline-block">
                                Setup Integration →
                            </a>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-bold">3</div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-gray-900">Add Activities</h4>
                            <p class="text-xs text-gray-600 mt-1">Create assignments, quizzes, and exams</p>
                            <a href="{{ route('activities.create') }}" class="text-xs text-green-600 hover:text-green-500 font-medium mt-1 inline-block">
                                Add Activities →
                            </a>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-sm font-bold">4</div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-gray-900">Map Students</h4>
                            <p class="text-xs text-gray-600 mt-1">Link Google Classroom students to school records</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-red-500 rounded-full flex items-center justify-center text-white text-sm font-bold">5</div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-gray-900">Input Grades</h4>
                            <p class="text-xs text-gray-600 mt-1">Use the grade matrix to input student scores</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Subjects Needing Attention -->
        @if(count($subjectsNeedingAttention) > 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                    Needs Attention
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($subjectsNeedingAttention as $item)
                    <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-yellow-800">{{ $item['subject']->subject_code }}</h4>
                                <div class="mt-1">
                                    @foreach($item['issues'] as $issue)
                                    <p class="text-xs text-yellow-700">• {{ $issue }}</p>
                                    @endforeach
                                </div>
                                <div class="mt-2 flex space-x-2">
                                    <a href="{{ route('subjects.show', $item['subject']) }}" class="text-xs text-yellow-800 hover:text-yellow-900 font-medium">
                                        View Subject →
                                    </a>
                                    @if(in_array('No Google Classroom connection', $item['issues']))
                                        <a href="{{ route('classroom.index') }}" class="text-xs text-yellow-800 hover:text-yellow-900 font-medium">
                                            Connect GCR →
                                        </a>
                                    @endif
                                    @if(in_array('No students mapped', $item['issues']))
                                        <a href="{{ route('mappings.index', $item['subject']) }}" class="text-xs text-yellow-800 hover:text-yellow-900 font-medium">
                                            Map Students →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Activities by Term Chart -->
@if($stats['total_activities'] > 0)
<div class="mt-8">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Activities Distribution</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-3 gap-4">
                @foreach(['prelim' => 'Prelims', 'midterm' => 'Midterm', 'finals' => 'Finals'] as $term => $label)
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['activities_by_term'][$term] ?? 0 }}</div>
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                    <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                        @php
                            $percentage = $stats['total_activities'] > 0 ? (($stats['activities_by_term'][$term] ?? 0) / $stats['total_activities']) * 100 : 0;
                        @endphp
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<!-- System Status and Links -->
<div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- System Status -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">System Status</h3>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Database Connection</span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        Active
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Google Classroom API</span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        Connected
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">School Database</span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        Synced
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Quick Links</h3>
        </div>
        <div class="p-6">
            <div class="space-y-2">
                <a href="{{ route('subjects.index') }}" class="block text-sm text-blue-600 hover:text-blue-500">
                    <i class="fas fa-book mr-2"></i>All Subjects
                </a>
                <a href="{{ route('activities.index') }}" class="block text-sm text-blue-600 hover:text-blue-500">
                    <i class="fas fa-tasks mr-2"></i>All Activities
                </a>
                <a href="{{ route('classroom.index') }}" class="block text-sm text-blue-600 hover:text-blue-500">
                    <i class="fab fa-google mr-2"></i>Google Classroom Sync
                </a>
                @if($recentSubjects->count() > 0)
                    <hr class="my-2">
                    @foreach($recentSubjects->take(3) as $subject)
                        <a href="{{ route('grades.matrix', $subject) }}" class="block text-sm text-gray-600 hover:text-gray-500">
                            <i class="fas fa-table mr-2"></i>{{ $subject->subject_code }} Grades
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Help & Support -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Help & Support</h3>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    <strong>Faculty Grading System</strong>
                </div>
                <div class="text-xs text-gray-500">
                    Streamline your grading workflow with Google Classroom integration, automated student mapping, and comprehensive grade management.
                </div>
                <div class="pt-2 border-t border-gray-200">
                    <div class="text-xs text-gray-500">
                        <strong>Features:</strong>
                        <ul class="mt-1 ml-4 list-disc">
                            <li>Google Classroom Integration</li>
                            <li>Automated Student Mapping</li>
                            <li>Interactive Grade Matrix</li>
                            <li>Activity Management</li>
                            <li>Real-time Synchronization</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection