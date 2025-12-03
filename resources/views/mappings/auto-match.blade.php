@extends('layouts.admin')

@section('page-title', 'Auto Match Students')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Auto Match Results</h1>
        <p class="text-gray-600 mt-2">
            Subject: {{ $subject->subject_code }} - {{ $subject->subject_name }} ({{ $subject->section }})
        </p>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('mappings.save-auto-matches', $subject) }}">
        @csrf
        
        <!-- High Confidence Matches -->
        @if(count($results['matches']) > 0)
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-green-700">
                        High Confidence Matches ({{ count($results['matches']) }})
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">These matches will be saved automatically</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    GCR Student
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    School Student
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Confidence
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Match Type
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($results['matches'] as $index => $match)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $match['gcr_student']['profile']['name']['fullName'] ?? 'Unknown' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            GCR ID: {{ substr($match['gcr_student']['userId'] ?? 'N/A', -8) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $match['school_student']->full_name ?? 'Unknown' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            ID: {{ $match['school_student']->student_number ?? 'No ID' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ number_format($match['confidence'] * 100, 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 capitalize">
                                            {{ str_replace('_', ' ', $match['match_type']) }}
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Hidden inputs for form submission -->
                                <input type="hidden" name="matches[{{ $index }}][gcr_student][userId]" 
                                       value="{{ $match['gcr_student']['userId'] ?? '' }}">
                                <input type="hidden" name="matches[{{ $index }}][gcr_student][profile][name][fullName]" 
                                       value="{{ $match['gcr_student']['profile']['name']['fullName'] ?? '' }}">
                                <input type="hidden" name="matches[{{ $index }}][gcr_student][emailAddress]" 
                                       value="{{ $match['gcr_student']['emailAddress'] ?? '' }}">
                                <input type="hidden" name="matches[{{ $index }}][school_student][id]" 
                                       value="{{ $match['school_student']->id ?? '' }}">
                                <input type="hidden" name="matches[{{ $index }}][confidence]" 
                                       value="{{ $match['confidence'] }}">
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Conflicts Requiring Manual Review -->
        @if(count($results['conflicts']) > 0)
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-yellow-700">
                        Conflicts Requiring Review ({{ count($results['conflicts']) }})
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">These matches need manual verification</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    GCR Student
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Possible Match
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Confidence
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($results['conflicts'] as $conflict)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $conflict['gcr_student']['profile']['name']['fullName'] ?? 'Unknown' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            GCR ID: {{ substr($conflict['gcr_student']['userId'] ?? 'N/A', -8) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(isset($conflict['possible_matches'][0]))
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $conflict['possible_matches'][0]['student']->full_name ?? 'Unknown' }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ID: {{ $conflict['possible_matches'][0]['student']->student_number ?? 'No ID' }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500">No matches found</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ number_format($conflict['confidence'] * 100, 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <span class="text-gray-500">Manual review required</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Unmatched Students -->
        @if(count($results['unmatched']) > 0)
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-red-700">
                        Unmatched Students ({{ count($results['unmatched']) }})
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">These students could not be automatically matched</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    GCR Student
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($results['unmatched'] as $student)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $student['profile']['name']['fullName'] ?? 'Unknown' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            GCR ID: {{ substr($student['userId'] ?? 'N/A', -8) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-red-600">No match found</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <a href="{{ route('mappings.index', $subject) }}" 
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                Back to Mappings
            </a>
            
            @if(count($results['matches']) > 0)
                <button type="submit" 
                        class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                    Save {{ count($results['matches']) }} High Confidence Matches
                </button>
            @endif
        </div>
    </form>
</div>
@endsection