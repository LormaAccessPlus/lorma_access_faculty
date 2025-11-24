@extends('layouts.admin')

@section('title', 'My Subjects')
@section('page-title', 'Subjects')

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
        <span class="text-gray-900 font-medium">Subjects</span>
    </li>
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Subjects</h1>
        <div class="flex space-x-3">
            <form action="{{ route('subjects.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    Sync from School DB
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Current Semester Subjects -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Current Semester</h2>
        </div>
        <div class="p-6">
            @if($currentSubjects->count() > 0)
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($currentSubjects as $subject)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-lg text-gray-900">{{ $subject->subject_code }}</h3>
                                <span class="px-2 py-1 text-xs rounded-full {{ $subject->type === 'lecture_lab' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $subject->type === 'lecture_lab' ? 'Lec + Lab' : 'Lecture Only' }}
                                </span>
                            </div>
                            <p class="text-gray-700 mb-2">{{ $subject->subject_name }}</p>
                            <p class="text-sm text-gray-600 mb-3">Section: {{ $subject->section }}</p>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs text-gray-500">{{ $subject->activities_count ?? 0 }} activities</span>
                                <div class="flex space-x-2">
                                    <a href="{{ route('subjects.show', $subject) }}" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                                    <a href="{{ route('subjects.edit', $subject) }}" class="text-green-600 hover:text-green-800 text-sm">Edit</a>
                                </div>
                            </div>
                            <!-- Quick Actions -->
                            <div class="flex flex-wrap gap-1">
                                <a href="{{ route('grades.matrix', $subject) }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200">
                                    <i class="fas fa-table mr-1"></i>
                                    Matrix
                                </a>
                                <a href="{{ route('grades.term', $subject) }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Terms
                                </a>
                                <a href="{{ route('mappings.index', $subject) }}" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-orange-700 bg-orange-100 hover:bg-orange-200">
                                    <i class="fas fa-users mr-1"></i>
                                    Students
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-600">No subjects found for the current semester.</p>
            @endif
        </div>
    </div>

    <!-- Past Semester Subjects -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Past Semesters</h2>
            @if(!request()->has('show_past'))
                <a href="{{ route('subjects.index', ['show_past' => 1]) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                    Show Past Subjects
                </a>
            @else
                <a href="{{ route('subjects.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">
                    Hide Past Subjects
                </a>
            @endif
        </div>
        @if(request()->has('show_past'))
            <div class="p-6">
                @if(count($pastSubjects) > 0)
                    @foreach($pastSubjects as $academicYear => $semesters)
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-800 mb-3">{{ $academicYear }}</h3>
                            @foreach($semesters as $semester => $subjects)
                                <div class="mb-4">
                                    <h4 class="text-md font-medium text-gray-700 mb-2">{{ $semester }}</h4>
                                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                        @foreach($subjects as $subject)
                                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                                <div class="flex justify-between items-start mb-1">
                                                    <h5 class="font-medium text-gray-900">{{ $subject->subject_code }}</h5>
                                                    <span class="px-2 py-1 text-xs rounded-full {{ $subject->type === 'lecture_lab' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                                        {{ $subject->type === 'lecture_lab' ? 'Lec + Lab' : 'Lecture Only' }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-700 mb-1">{{ $subject->subject_name }}</p>
                                                <p class="text-xs text-gray-600 mb-2">Section: {{ $subject->section }}</p>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-xs text-gray-500">{{ $subject->activities_count ?? 0 }} activities</span>
                                                    <a href="{{ route('subjects.show', $subject) }}" class="text-blue-600 hover:text-blue-800 text-xs">View</a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <p class="text-gray-600">No past subjects found.</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection