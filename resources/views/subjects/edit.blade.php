@extends('layouts.admin')

@section('title', 'Edit Subject - ' . $subject->subject_code)
@section('page-title', 'Edit Subject')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Edit Subject</h1>
            <div class="flex space-x-3">
                <a href="{{ route('subjects.show', $subject) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    View Subject
                </a>
                <a href="{{ route('subjects.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    Back to Subjects
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('subjects.update', $subject) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="subject_code" class="block text-sm font-medium text-gray-700 mb-2">Subject Code *</label>
                        <input type="text" 
                               id="subject_code" 
                               name="subject_code" 
                               value="{{ old('subject_code', $subject->subject_code) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('subject_code') border-red-500 @enderror"
                               required>
                        @error('subject_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="section" class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                        <input type="text" 
                               id="section" 
                               name="section" 
                               value="{{ old('section', $subject->section) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('section') border-red-500 @enderror"
                               required>
                        @error('section')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="subject_name" class="block text-sm font-medium text-gray-700 mb-2">Subject Name *</label>
                    <input type="text" 
                           id="subject_name" 
                           name="subject_name" 
                           value="{{ old('subject_name', $subject->subject_name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('subject_name') border-red-500 @enderror"
                           required>
                    @error('subject_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Subject Type *</label>
                    <select id="type" 
                            name="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('type') border-red-500 @enderror"
                            required>
                        <option value="">Select subject type</option>
                        <option value="lecture_only" {{ old('type', $subject->type) === 'lecture_only' ? 'selected' : '' }}>Lecture Only</option>
                        <option value="lab_only" {{ old('type', $subject->type) === 'lab_only' ? 'selected' : '' }}>Lab Only</option>
                        <option value="lecture_lab" {{ old('type', $subject->type) === 'lecture_lab' ? 'selected' : '' }}>Lecture + Laboratory</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2 mt-6">
                    <div>
                        <label for="academic_year" class="block text-sm font-medium text-gray-700 mb-2">Academic Year *</label>
                        <input type="text" 
                               id="academic_year" 
                               name="academic_year" 
                               value="{{ old('academic_year', $subject->academic_year) }}"
                               placeholder="2024-2025"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('academic_year') border-red-500 @enderror"
                               required>
                        @error('academic_year')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="semester" class="block text-sm font-medium text-gray-700 mb-2">Semester *</label>
                        <select id="semester" 
                                name="semester" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('semester') border-red-500 @enderror"
                                required>
                            <option value="">Select semester</option>
                            <option value="1st Semester" {{ old('semester', $subject->semester) === '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                            <option value="2nd Semester" {{ old('semester', $subject->semester) === '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                            <option value="Summer" {{ old('semester', $subject->semester) === 'Summer' ? 'selected' : '' }}>Summer</option>
                        </select>
                        @error('semester')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="school_subject_id" class="block text-sm font-medium text-gray-700 mb-2">School Database Subject ID</label>
                    <input type="number" 
                           id="school_subject_id" 
                           name="school_subject_id" 
                           value="{{ old('school_subject_id', $subject->school_subject_id) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('school_subject_id') border-red-500 @enderror"
                           placeholder="Leave empty if not linked to school database">
                    @error('school_subject_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-600">Optional: Link this subject to an existing record in the school database</p>
                </div>

                <div class="mt-6">
                    <label for="gcr_class_id" class="block text-sm font-medium text-gray-700 mb-2">Google Classroom Class ID</label>
                    <input type="text" 
                           id="gcr_class_id" 
                           name="gcr_class_id" 
                           value="{{ old('gcr_class_id', $subject->gcr_class_id) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('gcr_class_id') border-red-500 @enderror"
                           placeholder="Leave empty if not connected to Google Classroom">
                    @error('gcr_class_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-600">Optional: Connect this subject to a Google Classroom class</p>
                </div>

                <div class="flex justify-between items-center mt-8">
                    <form action="{{ route('subjects.destroy', $subject) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this subject? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200">
                            Delete Subject
                        </button>
                    </form>
                    
                    <div class="flex space-x-3">
                        <a href="{{ route('subjects.show', $subject) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                            Update Subject
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection