@extends('layouts.admin')

@section('title', 'Activities')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Activities</h1>
                <p class="text-gray-600">Manage your course activities and assignments</p>
            </div>
            <div class="flex space-x-3">
                @if(!$selectedSubject)
                    <div class="text-sm text-gray-600 bg-blue-50 px-4 py-2 rounded-lg border border-blue-200">
                        <i class="fas fa-info-circle mr-2"></i>Select a subject to manage activities
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
        <form method="GET" action="{{ route('activities.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-64">
                <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <select name="subject_id" id="subject_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ $subjectId == $subject->id ? 'selected' : '' }}>
                            {{ $subject->subject_code }} - {{ $subject->subject_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="min-w-32">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" id="type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="lecture" {{ $type == 'lecture' ? 'selected' : '' }}>Lecture</option>
                    <option value="lab" {{ $type == 'lab' ? 'selected' : '' }}>Lab</option>
                </select>
            </div>
            
            <div class="min-w-32">
                <label for="term" class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                <select name="term" id="term" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Terms</option>
                    <option value="prelim" {{ $term == 'prelim' ? 'selected' : '' }}>Prelim</option>
                    <option value="midterm" {{ $term == 'midterm' ? 'selected' : '' }}>Midterm</option>
                    <option value="finals" {{ $term == 'finals' ? 'selected' : '' }}>Finals</option>
                </select>
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="{{ route('activities.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md font-medium transition-colors">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    @if($selectedSubject)
        <!-- Enhanced Activity Manager Component -->
        @include('activities._activity-manager', ['subject' => $selectedSubject])
    @elseif($activities->count() > 0)
        @if(!$selectedSubject)
            <!-- All activities list view -->
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">All Activities ({{ $activities->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($activities as $activity)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $activity->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $activity->subject->subject_code }}</div>
                                        <div class="text-sm text-gray-500">{{ $activity->subject->subject_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $activity->type === 'lecture' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            <i class="fas {{ $activity->type === 'lecture' ? 'fa-chalkboard-teacher' : 'fa-flask' }} mr-1"></i>
                                            {{ ucfirst($activity->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($activity->term) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $activity->max_score }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $activity->weight ? $activity->weight . '%' : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('activities.edit', $activity) }}" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('activities.destroy', $activity) }}" 
                                                  class="inline" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <!-- Empty state -->
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-tasks text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Activities Found</h3>
            <p class="text-gray-600 mb-6">
                @if($subjectId || $type || $term)
                    No activities match your current filters. Try adjusting your search criteria.
                @else
                    You haven't created any activities yet. Select a subject to get started.
                @endif
            </p>
            @if($subjects->count() > 0)
                <p class="text-gray-500">Select a subject above to manage activities</p>
            @else
                <p class="text-gray-500">You need to have subjects before you can create activities.</p>
                <a href="{{ route('subjects.index') }}" 
                   class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-book mr-2"></i>Go to Subjects
                </a>
            @endif
        </div>
    @endif
</div>

<script>
// Auto-submit form when subject changes
document.getElementById('subject_id').addEventListener('change', function() {
    this.form.submit();
});
</script>
@endsection