@extends('layouts.admin')

@section('title', $subject->subject_code . ' - ' . $subject->subject_name)
@section('page-title', $subject->subject_code)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $subject->subject_code }}</h1>
            <p class="text-lg text-gray-700">{{ $subject->subject_name }}</p>
            <p class="text-sm text-gray-600">Section {{ $subject->section }} • {{ $subject->academic_year }} {{ $subject->semester }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('subjects.edit', $subject) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                Edit Subject
            </a>
            <a href="{{ route('subjects.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                Back to Subjects
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Subject Information -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Subject Information</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subject Code</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $subject->subject_code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subject Name</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $subject->subject_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Section</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $subject->section }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs rounded-full {{ $subject->type === 'lecture_lab' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $subject->type === 'lecture_lab' ? 'Lecture + Laboratory' : 'Lecture Only' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $subject->academic_year }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Semester</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $subject->semester }}</p>
                    </div>
                    @if($subject->gcr_class_id)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Google Classroom</label>
                            <p class="mt-1 text-sm text-gray-900 flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                                Connected (Class ID: {{ $subject->gcr_class_id }})
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Activities Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Activities</h2>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        Add Activity
                    </button>
                </div>
                
                @if($subject->activities->count() > 0)
                    <div class="space-y-4">
                        @if($subject->activities->where('type', 'lecture')->count() > 0)
                            <div>
                                <h3 class="text-lg font-medium text-gray-800 mb-2">Lecture Activities</h3>
                                <div class="space-y-2">
                                    @foreach($subject->activities->where('type', 'lecture') as $activity)
                                        <div class="flex justify-between items-center p-3 border border-gray-200 rounded-lg">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $activity->name }}</p>
                                                <p class="text-sm text-gray-600">{{ $activity->term }} • Max Score: {{ $activity->max_score }}</p>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                                <button class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($subject->hasLaboratory() && $subject->activities->where('type', 'lab')->count() > 0)
                            <div>
                                <h3 class="text-lg font-medium text-gray-800 mb-2">Laboratory Activities</h3>
                                <div class="space-y-2">
                                    @foreach($subject->activities->where('type', 'lab') as $activity)
                                        <div class="flex justify-between items-center p-3 border border-gray-200 rounded-lg bg-purple-50">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $activity->name }}</p>
                                                <p class="text-sm text-gray-600">{{ $activity->term }} • Max Score: {{ $activity->max_score }}</p>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                                <button class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-gray-600">No activities added yet.</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Stats</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Total Activities</span>
                        <span class="text-sm font-medium text-gray-900">{{ $subject->activities->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Lecture Activities</span>
                        <span class="text-sm font-medium text-gray-900">{{ $subject->activities->where('type', 'lecture')->count() }}</span>
                    </div>
                    @if($subject->hasLaboratory())
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Lab Activities</span>
                            <span class="text-sm font-medium text-gray-900">{{ $subject->activities->where('type', 'lab')->count() }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Students</span>
                        <span class="text-sm font-medium text-gray-900">{{ $subject->studentMappings->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('grades.matrix', $subject) }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 text-center">
                        Grade Matrix
                    </a>
                    <a href="{{ route('grades.term', $subject) }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200 text-center">
                        Term-Based Grading
                    </a>
                    <a href="{{ route('mappings.index', $subject) }}" class="block w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200 text-center">
                        Manage Students
                    </a>
                    @if(!$subject->gcr_class_id)
                        <button class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition duration-200">
                            Connect GCR
                        </button>
                    @endif
                    <button class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        Export Grades
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection