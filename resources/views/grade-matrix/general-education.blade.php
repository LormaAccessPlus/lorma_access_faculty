@extends('layouts.admin')

@section('title', 'General Education Matrix')

@section('page-title', 'General Education Matrix')

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span>Grade Matrix</span>
    </li>
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span>General Education Matrix</span>
    </li>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">General Education Matrix</h2>
                <p class="mt-1 text-sm text-gray-600">Manage grades using standard general education grading system</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 text-sm font-medium text-teal-800 bg-teal-100 rounded-full">
                    {{ $subjects->count() }} {{ Str::plural('Subject', $subjects->count()) }}
                </span>
            </div>
        </div>
    </div>

    @if($subjects->isEmpty())
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-calculator text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Subjects Available</h3>
            <p class="text-gray-600 mb-6">You need to map subjects before you can manage grades.</p>
            <a href="{{ route('classroom.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:opacity-90 transition-opacity"
               style="background-color: #08695A;">
                <i class="fas fa-link mr-2"></i>
                Map a Subject
            </a>
        </div>
    @else
        <!-- Subjects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($subjects as $subject)
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <!-- Subject Header with Colored Background -->
                    <div class="px-6 py-4 relative" style="background: linear-gradient(135deg, #08695A 0%, #0A7B6A 100%);">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-white mb-1">{{ $subject->subject_name }}</h3>
                                <p class="text-sm text-teal-100 font-medium">{{ $subject->subject_code }}</p>
                            </div>
                            @if($subject->gcr_class_id)
                                <span class="px-2 py-1 text-xs font-medium text-white bg-white bg-opacity-20 rounded-full backdrop-blur-sm">
                                    <i class="fab fa-google mr-1"></i>GCR
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-gray-200">
                            <div>
                                <p class="text-xs text-gray-500">Students</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $subject->studentMappings->count() }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Activities</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $subject->activities->count() }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-2">
                            <a href="{{ route('grade-matrix.general-education.matrix', $subject) }}" 
                               class="flex items-center justify-between px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                               style="background-color: #08695A;"
                               onmouseover="this.style.backgroundColor='#065A4A';"
                               onmouseout="this.style.backgroundColor='#08695A';">
                                <span><i class="fas fa-table mr-2"></i>Full Grade Matrix</span>
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                            <a href="{{ route('grade-matrix.general-education.term', $subject) }}" 
                               class="flex items-center justify-between px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                <span><i class="fas fa-edit mr-2"></i>Edit Term Grades</span>
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>

                        <!-- Export Options -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-xs font-medium text-gray-500 mb-2">Export Options</p>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('grades.export.activities', $subject) }}" 
                                   class="inline-flex items-center px-3 py-1 text-xs font-medium text-orange-700 bg-orange-50 rounded hover:bg-orange-100 transition-colors">
                                    <i class="fas fa-file-pdf mr-1"></i>Activities
                                </a>
                                <a href="{{ route('grades.export.pp', $subject) }}" 
                                   class="inline-flex items-center px-3 py-1 text-xs font-medium text-purple-700 bg-purple-50 rounded hover:bg-purple-100 transition-colors">
                                    <i class="fas fa-file-pdf mr-1"></i>Grades (PP)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
