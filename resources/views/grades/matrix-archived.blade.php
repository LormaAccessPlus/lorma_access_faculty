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
        <h3 class="text-lg font-medium text-gray-900 mb-4">Export Options</h3>
        <div class="flex gap-4">
            <button onclick="exportMatrixPDF()" 
               class="flex items-center px-6 py-3 border border-gray-300 rounded-lg hover:bg-red-50 hover:border-red-300 transition-colors">
                <i class="fas fa-file-pdf text-red-500 text-xl mr-3"></i>
                <div>
                    <div class="font-medium text-gray-900">Export as PDF</div>
                    <div class="text-sm text-gray-500">Grade matrix report</div>
                </div>
            </button>
            
            <button onclick="exportMatrixCSV()" 
               class="flex items-center px-6 py-3 border border-gray-300 rounded-lg hover:bg-green-50 hover:border-green-300 transition-colors">
                <i class="fas fa-file-csv text-green-500 text-xl mr-3"></i>
                <div>
                    <div class="font-medium text-gray-900">Export as CSV</div>
                    <div class="text-sm text-gray-500">Spreadsheet format</div>
                </div>
            </button>
            
            <a href="{{ route('grades.term', [$subject, 'prelim']) }}" 
               class="flex items-center px-6 py-3 border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-colors">
                <i class="fas fa-eye text-blue-500 text-xl mr-3"></i>
                <div>
                    <div class="font-medium text-gray-900">View Term Grades</div>
                    <div class="text-sm text-gray-500">Detailed term view</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Export Script -->
    <script>
    function exportMatrixPDF() {
        const deanName = prompt('Enter Dean\'s name for the PDF signature:', '');
        if (deanName !== null) {
            window.location.href = '{{ route('archive.export-pdf', $subject) }}?dean_name=' + encodeURIComponent(deanName);
        }
    }
    
    function exportMatrixCSV() {
        window.location.href = '{{ route('grading.export-full-matrix-csv', $subject->id) }}';
    }
    </script>
</div>
@endsection
