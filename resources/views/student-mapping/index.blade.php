@extends('layouts.admin')

@section('title', 'Student Mapping')

@section('page-title', 'Student Mapping')

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span class="text-gray-900">Student Mapping</span>
    </li>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Student Mapping</h2>
                <p class="mt-1 text-sm text-gray-600">Map Google Classroom students to school database students</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-full">
                    {{ $subjects->count() }} {{ Str::plural('Subject', $subjects->count()) }}
                </span>
            </div>
        </div>
    </div>

    @if($subjects->isEmpty())
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-users text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Subjects Available</h3>
            <p class="text-gray-600 mb-6">You need to create or map subjects before you can map students.</p>
            <a href="{{ route('subjects.mapping.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: #08695A;">
                <i class="fas fa-link mr-2"></i>
                Map a Subject
            </a>
        </div>
    @else
        <!-- Subjects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($subjects as $subject)
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <!-- Subject Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $subject->subject_code }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $subject->subject_name }}</p>
                            </div>
                            @if($subject->isArchived())
                                <span class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full">
                                    Archived
                                </span>
                            @endif
                        </div>

                        <!-- Mapping Stats -->
                        <div class="mb-4">
                            @php
                                $totalStudents = $subject->studentMappings->count();
                                $mappedStudents = $subject->studentMappings->whereNotNull('student_id')->count();
                                $mappingPercentage = $totalStudents > 0 ? round(($mappedStudents / $totalStudents) * 100) : 0;
                            @endphp
                            
                            <div class="flex items-center justify-between text-sm mb-2">
                                <span class="text-gray-600">Mapping Progress</span>
                                <span class="font-medium text-gray-900">{{ $mappedStudents }}/{{ $totalStudents }}</span>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all" 
                                     style="width: {{ $mappingPercentage }}%; background-color: {{ $mappingPercentage == 100 ? '#10b981' : '#08695A' }};">
                                </div>
                            </div>
                            
                            @if($mappingPercentage == 100)
                                <p class="text-xs text-green-600 mt-2">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    All students mapped
                                </p>
                            @elseif($mappingPercentage > 0)
                                <p class="text-xs text-orange-600 mt-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $totalStudents - $mappedStudents }} students need mapping
                                </p>
                            @else
                                <p class="text-xs text-red-600 mt-2">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    No students mapped yet
                                </p>
                            @endif
                        </div>

                        <!-- Action Button -->
                        <a href="{{ route('mappings.index', $subject) }}" class="block w-full text-center px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-opacity" style="background-color: #08695A;">
                            <i class="fas fa-users mr-2"></i>
                            Manage Student Mapping
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
