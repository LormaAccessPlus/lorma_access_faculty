@extends('layouts.admin')

@section('page-title', 'Create Activity')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Add New Activity</h1>
                <a href="{{ route('subjects.show', $subject) }}" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Back to Subject
                </a>
            </div>

            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <h2 class="font-semibold text-gray-700">{{ $subject->subject_code }} - {{ $subject->subject_name }}</h2>
                <p class="text-sm text-gray-600">Section: {{ $subject->section }}</p>
            </div>

            <form id="activity-form" class="space-y-6">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Activity Name *
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., Quiz 1, Laboratory Exercise 1">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                            Activity Type *
                        </label>
                        <select id="type" 
                                name="type" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="lecture">Lecture</option>
                            @if($subject->type === 'lecture_lab')
                                <option value="lab">Laboratory</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="term" class="block text-sm font-medium text-gray-700 mb-2">
                            Term *
                        </label>
                        <select id="term" 
                                name="term" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Term</option>
                            <option value="prelim">Prelims</option>
                            <option value="midterm">Midterm</option>
                            <option value="finals">Finals</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="max_score" class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Score *
                        </label>
                        <input type="number" 
                               id="max_score" 
                               name="max_score" 
                               required
                               min="0" 
                               step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., 100">
                    </div>

                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                            Weight (%)
                        </label>
                        <input type="number" 
                               id="weight" 
                               name="weight" 
                               min="0" 
                               max="100" 
                               step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Optional">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                    <a href="{{ route('subjects.show', $subject) }}" 
                       class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">
                        Create Activity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('activity-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('{{ route("activities.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            alert('Activity created successfully!');
            window.location.href = '{{ route("subjects.show", $subject) }}';
        } else {
            alert('Error: ' + (result.message || 'Failed to create activity'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
@endsection