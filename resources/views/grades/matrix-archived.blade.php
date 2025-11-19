@extends('layouts.admin')

@section('page-title', 'Grade Matrix - Archived')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Archived Notice -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-archive text-yellow-400 text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-yellow-800">
                    This Subject is Archived
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>
                        <strong>{{ $subject->subject_code }} - {{ $subject->subject_name }}</strong> ({{ $subject->section }})
                    </p>
                    <p class="mt-2">
                        This subject has been archived in Google Classroom. Grade entry and modifications are disabled.
                        You can still view existing grades and export reports.
                    </p>
                </div>
                <div class="mt-4">
                    <a href="{{ route('subjects.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Subjects
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Actions -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Available Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('grades.export.activities', $subject) }}" 
               class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                <div>
                    <div class="font-medium text-gray-900">Export Activities</div>
                    <div class="text-sm text-gray-500">Download PDF report</div>
                </div>
            </a>
            
            <a href="{{ route('grades.export.pp', $subject) }}" 
               class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                <div>
                    <div class="font-medium text-gray-900">Export Grades (PP)</div>
                    <div class="text-sm text-gray-500">Download PDF report</div>
                </div>
            </a>
            
            <a href="{{ route('grades.term', [$subject, 'prelim']) }}" 
               class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-eye text-blue-500 text-2xl mr-3"></i>
                <div>
                    <div class="font-medium text-gray-900">View Term Grades</div>
                    <div class="text-sm text-gray-500">Read-only view</div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
