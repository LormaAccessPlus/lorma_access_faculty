@extends('layouts.admin')

@section('title', 'Term Grades - ' . $subject->subject_code)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Term-Based Grading</h1>
                <p class="text-gray-600">{{ $subject->subject_code }} - {{ $subject->subject_name }} ({{ $subject->section }})</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('grades.matrix', $subject) }}" class="text-white px-4 py-2 rounded-lg font-medium transition-colors"
                   style="background-color: #08695A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#08695A';">
                    <i class="fas fa-table mr-2"></i>Full Matrix
                </a>
                <a href="{{ route('subjects.show', $subject) }}" class="px-4 py-2 rounded-lg font-medium transition-colors border"
                   style="border-color: #08695A; color: #08695A; background-color: white;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Subject
                </a>
            </div>
        </div>
    </div>

    <!-- Term Navigation Tabs -->
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                @foreach(['prelim', 'midterm', 'finals'] as $termKey)
                    <a href="{{ route('grades.term', ['subject' => $subject, 'term' => $termKey]) }}"
                       class="py-4 px-4 border-b-2 font-medium text-sm transition-colors {{ $term === $termKey ? 'text-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                       style="{{ $term === $termKey ? 'border-color: #08695A; background-color: #08695A; border-radius: 8px 8px 0 0; margin-bottom: -1px;' : '' }}">
                        <span class="capitalize">{{ $termKey }}</span>
                        @if(isset($termProgress[$termKey]))
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $termProgress[$termKey]['percentage'] == 100 ? 'bg-green-100 text-green-800' : ($termProgress[$termKey]['percentage'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $termProgress[$termKey]['percentage'] ?? 0 }}%
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Grade Calculator Section -->
        @include('grades._grading-calculator')

        <!-- Old Configuration (Hidden for now) -->
        <div class="p-6 border-b border-gray-200 bg-gray-50 hidden" x-data="{ 
            showConfig: false,
            showFinalConfig: false,
            termConfig: {
                class_standing_weight: {{ $gradingConfig->class_standing_weight ?? 40 }},
                exam_weight: {{ $gradingConfig->exam_weight ?? 60 }}
            },
            finalConfig: {
                prelim_weight: {{ $finalRatingConfig['prelim_weight'] ?? 30 }},
                midterm_weight: {{ $finalRatingConfig['midterm_weight'] ?? 30 }},
                finals_weight: {{ $finalRatingConfig['finals_weight'] ?? 40 }}
            }
        }">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-calculator mr-2" style="color: #08695A;"></i>
                        Grading Formula Configuration
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Define how grades are computed for this term and overall</p>
                </div>
                <div class="flex space-x-2">
                    <button @click="showConfig = !showConfig" 
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors border"
                            :class="showConfig ? 'bg-white text-gray-700 border-gray-300' : 'text-white border-transparent'"
                            :style="!showConfig ? 'background-color: #08695A;' : ''">
                        <i class="fas fa-cog mr-2"></i>
                        <span x-text="showConfig ? 'Hide Config' : 'Configure Term'"></span>
                    </button>
                    <button @click="showFinalConfig = !showFinalConfig" 
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors border"
                            :class="showFinalConfig ? 'bg-white text-gray-700 border-gray-300' : 'text-white border-transparent'"
                            :style="!showFinalConfig ? 'background-color: #E67E22;' : ''">
                        <i class="fas fa-trophy mr-2"></i>
                        <span x-text="showFinalConfig ? 'Hide Final Config' : 'Configure Final Rating'"></span>
                    </button>
                </div>
            </div>

            <!-- Current Configuration Display -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                        Current {{ ucfirst($term) }} Term Formula
                    </h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Class Standing:</strong> <span x-text="termConfig.class_standing_weight"></span>%</p>
                        <p><strong>Exam:</strong> <span x-text="termConfig.exam_weight"></span>%</p>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            @if($term === 'prelim')
                                Prelim uses percentage: (Total Score / Total Possible) × 100
                            @else
                                Midterm/Finals use transmuted: (Score / Items) × 50 + 50
                            @endif
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-trophy mr-2 text-orange-600"></i>
                        Final Rating Formula
                    </h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Prelim:</strong> <span x-text="finalConfig.prelim_weight"></span>%</p>
                        <p><strong>Midterm:</strong> <span x-text="finalConfig.midterm_weight"></span>%</p>
                        <p><strong>Finals:</strong> <span x-text="finalConfig.finals_weight"></span>%</p>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Final Rating = (Prelim × <span x-text="finalConfig.prelim_weight"></span>%) + (Midterm × <span x-text="finalConfig.midterm_weight"></span>%) + (Finals × <span x-text="finalConfig.finals_weight"></span>%)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Term Configuration Form -->
            <div x-show="showConfig" x-cloak x-transition class="bg-white rounded-lg p-6 border border-gray-300 mb-4">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Configure {{ ucfirst($term) }} Term Grading</h4>
                
                <!-- Activity Weights Section -->
                @if(isset($lectureActivities) && $lectureActivities->isNotEmpty() || isset($labActivities) && $labActivities->isNotEmpty())
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h5 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-tasks mr-2 text-blue-600"></i>
                        Activity Weights for Class Standing
                    </h5>
                    <p class="text-xs text-gray-600 mb-4">
                        Set individual weights for each activity. Weights will be used to calculate the class standing component.
                    </p>
                    
                    <div class="space-y-4">
                        @if(isset($lectureActivities) && $lectureActivities->isNotEmpty())
                        <div>
                            <h6 class="text-xs font-semibold text-gray-700 mb-2 uppercase">Lecture Activities</h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($lectureActivities as $activity)
                                <div class="flex items-center justify-between bg-white p-3 rounded border border-gray-200">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $activity->name }}</p>
                                        <p class="text-xs text-gray-500">Max: {{ $activity->max_score }}</p>
                                    </div>
                                    <div class="ml-3">
                                        <input type="number" 
                                               name="activity_weights[{{ $activity->id }}]"
                                               value="{{ $activity->weight ?? 0 }}"
                                               min="0"
                                               max="100"
                                               step="0.01"
                                               class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                                               placeholder="0">
                                        <span class="text-xs text-gray-500 ml-1">%</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($labActivities) && $labActivities->isNotEmpty())
                        <div>
                            <h6 class="text-xs font-semibold text-gray-700 mb-2 uppercase">Laboratory Activities</h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($labActivities as $activity)
                                <div class="flex items-center justify-between bg-white p-3 rounded border border-gray-200">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $activity->name }}</p>
                                        <p class="text-xs text-gray-500">Max: {{ $activity->max_score }}</p>
                                    </div>
                                    <div class="ml-3">
                                        <input type="number" 
                                               name="activity_weights[{{ $activity->id }}]"
                                               value="{{ $activity->weight ?? 0 }}"
                                               min="0"
                                               max="100"
                                               step="0.01"
                                               class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                                               placeholder="0">
                                        <span class="text-xs text-gray-500 ml-1">%</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        <div class="pt-3 border-t border-blue-200">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-lightbulb mr-1 text-yellow-500"></i>
                                <strong>Tip:</strong> If weights don't sum to 100%, they will be normalized automatically. Leave at 0 for equal weighting.
                            </p>
                        </div>
                    </div>
                </div>
                @endif
                
                <form @submit.prevent="saveTermConfig" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Class Standing Weight (%)
                            </label>
                            <input type="number" 
                                   x-model="termConfig.class_standing_weight" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Weight of class standing in term grade</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Exam Weight (%)
                            </label>
                            <input type="number" 
                                   x-model="termConfig.exam_weight" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Weight of exam in term grade</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t">
                        <p class="text-sm text-gray-600">
                            Total: <span class="font-semibold" x-text="(parseFloat(termConfig.class_standing_weight) + parseFloat(termConfig.exam_weight)).toFixed(2)"></span>% 
                            <span x-show="(parseFloat(termConfig.class_standing_weight) + parseFloat(termConfig.exam_weight)) !== 100" class="text-red-600 ml-2">
                                <i class="fas fa-exclamation-triangle"></i> Must equal 100%
                            </span>
                        </p>
                        <button type="submit" 
                                class="px-6 py-2 text-white rounded-lg font-medium transition-colors"
                                style="background-color: #08695A;"
                                onmouseover="this.style.backgroundColor='#065A4A';"
                                onmouseout="this.style.backgroundColor='#08695A';">
                            <i class="fas fa-save mr-2"></i>Save Term Config
                        </button>
                    </div>
                </form>
            </div>

            <!-- Final Rating Configuration Form -->
            <div x-show="showFinalConfig" x-cloak x-transition class="bg-white rounded-lg p-6 border border-gray-300">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Configure Final Rating Computation</h4>
                <form @submit.prevent="saveFinalConfig" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Prelim Weight (%)
                            </label>
                            <input type="number" 
                                   x-model="finalConfig.prelim_weight" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Midterm Weight (%)
                            </label>
                            <input type="number" 
                                   x-model="finalConfig.midterm_weight" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Finals Weight (%)
                            </label>
                            <input type="number" 
                                   x-model="finalConfig.finals_weight" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   required>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t">
                        <p class="text-sm text-gray-600">
                            Total: <span class="font-semibold" x-text="(parseFloat(finalConfig.prelim_weight) + parseFloat(finalConfig.midterm_weight) + parseFloat(finalConfig.finals_weight)).toFixed(2)"></span>% 
                            <span x-show="(parseFloat(finalConfig.prelim_weight) + parseFloat(finalConfig.midterm_weight) + parseFloat(finalConfig.finals_weight)) !== 100" class="text-red-600 ml-2">
                                <i class="fas fa-exclamation-triangle"></i> Must equal 100%
                            </span>
                        </p>
                        <button type="submit" 
                                class="px-6 py-2 text-white rounded-lg font-medium transition-colors"
                                style="background-color: #E67E22;"
                                onmouseover="this.style.backgroundColor='#D35400';"
                                onmouseout="this.style.backgroundColor='#E67E22';">
                            <i class="fas fa-save mr-2"></i>Save Final Config
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($subject->studentMappings) && $subject->studentMappings->isEmpty())
            <div class="p-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                No students mapped for this subject. Please map students first.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Term Content -->
            <div class="p-6">
                <!-- Progress Section -->
                @if(isset($termProgress[$term]))
                <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-4 mb-6 border border-green-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">
                                <span class="capitalize">{{ $term }}</span> Term Progress
                            </h3>
                            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                                <span>{{ $termProgress[$term]['students_with_grades'] ?? 0 }} of {{ $termProgress[$term]['total_students'] ?? 0 }} students completed</span>
                                <span class="font-semibold">{{ $termProgress[$term]['percentage'] ?? 0 }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all duration-300" 
                                     style="width: {{ $termProgress[$term]['percentage'] ?? 0 }}%; background: linear-gradient(90deg, #08695A 0%, #0A7B6A 100%);"></div>
                            </div>
                        </div>
                        <div class="ml-6 flex items-center space-x-3">
                            @if($subject->gcr_class_id && ((isset($lectureActivities) && $lectureActivities->where('gcr_assignment_id', '!=', null)->isNotEmpty()) || (isset($labActivities) && $labActivities->where('gcr_assignment_id', '!=', null)->isNotEmpty())))
                                <button id="import-grades-btn" 
                                        class="flex items-center px-4 py-2 text-white rounded-lg font-medium transition-all duration-200 shadow-sm hover:shadow-md"
                                        style="background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);"
                                        onmouseover="this.style.transform='translateY(-1px)';"
                                        onmouseout="this.style.transform='translateY(0)';">
                                    <i class="fab fa-google mr-2"></i>
                                    Import from Classroom
                                </button>
                            @endif
                            <a href="{{ route('subjects.show', $subject) }}#activities" 
                               class="flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600 transition-all duration-200 shadow-sm hover:shadow-md"
                               onmouseover="this.style.transform='translateY(-1px)';"
                               onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-cog mr-2"></i>
                                Manage Activities
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Grading Instructions -->
                <div class="bg-blue-50 rounded-lg p-4 mb-6 border border-blue-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800 capitalize">{{ $term }} Grading Instructions</h4>
                            <div class="flex items-center text-xs mt-1 text-blue-700">
                                <i class="fas fa-keyboard mr-2"></i>
                                <span>Press <kbd class="px-1 py-0.5 bg-blue-200 rounded text-xs">Enter</kbd> to save grades • Click cells to input scores</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activities and Exam Scores Table -->
                <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100 sticky left-0 z-10 min-w-48">
                                    Student Name
                                </th>
                                @if(isset($lectureActivities) && $lectureActivities->isNotEmpty())
                                    <th colspan="{{ $lectureActivities->count() }}" class="px-6 py-3 text-center text-xs font-medium text-blue-700 uppercase tracking-wider bg-blue-50 border-l border-blue-200">
                                        Lecture Activities
                                    </th>
                                @endif
                                @if(isset($labActivities) && $labActivities->isNotEmpty() && $subject->type === 'lecture_lab')
                                    <th colspan="{{ $labActivities->count() }}" class="px-6 py-3 text-center text-xs font-medium text-purple-700 uppercase tracking-wider bg-purple-50 border-l border-purple-200">
                                        Laboratory Activities
                                    </th>
                                @endif
                                <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-teal-50 border-l border-teal-200 min-w-32">
                                    Total CS
                                </th>
                                <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50 border-l border-green-200 min-w-32">
                                    Class Standing
                                </th>
                                <th colspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50 border-l border-yellow-200">
                                    Exam
                                </th>
                                <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-orange-50 border-l border-orange-200 min-w-32">
                                    Exam Grade
                                </th>
                                <th rowspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-red-50 border-l border-red-200 min-w-32">
                                    <span class="capitalize">{{ $term }}</span> Grade
                                </th>
                            </tr>
                            <tr>
                                @if(isset($lectureActivities))
                                    @foreach($lectureActivities as $activity)
                                        <th class="px-3 py-2 text-center text-xs font-medium text-blue-600 bg-blue-25 border-l border-blue-100 min-w-20" title="{{ $activity->name }}{{ $activity->gcr_assignment_id ? ' (Connected to Google Classroom)' : '' }}">
                                            <div class="font-semibold flex items-center justify-center">
                                                {{ Str::limit($activity->name, 8) }}
                                                @if($activity->gcr_assignment_id)
                                                    <i class="fab fa-google text-xs ml-1" title="Connected to Google Classroom"></i>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">/{{ $activity->max_score }}</div>
                                        </th>
                                    @endforeach
                                @endif
                                @if(isset($labActivities) && $subject->type === 'lecture_lab')
                                    @foreach($labActivities as $activity)
                                        <th class="px-3 py-2 text-center text-xs font-medium text-purple-600 bg-purple-25 border-l border-purple-100 min-w-20" title="{{ $activity->name }}{{ $activity->gcr_assignment_id ? ' (Connected to Google Classroom)' : '' }}">
                                            <div class="font-semibold flex items-center justify-center">
                                                {{ Str::limit($activity->name, 8) }}
                                                @if($activity->gcr_assignment_id)
                                                    <i class="fab fa-google text-xs ml-1" title="Connected to Google Classroom"></i>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">/{{ $activity->max_score }}</div>
                                        </th>
                                    @endforeach
                                @endif
                                <th class="px-3 py-2 text-center text-xs font-medium text-yellow-600 bg-yellow-25 border-l border-yellow-100 min-w-20">
                                    Score
                                </th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-yellow-600 bg-yellow-25 border-l border-yellow-100 min-w-20">
                                    Max
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if(isset($subject->studentMappings))
                                @foreach($subject->studentMappings as $studentMapping)
                                    @php
                                        $termGrade = isset($termGrades) ? ($termGrades[$studentMapping->id] ?? null) : null;
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap bg-gray-50 sticky left-0 z-10 border-r border-gray-200">
                                            <div class="text-sm font-medium text-gray-900">{{ $studentMapping->student_name }}</div>
                                            @if(isset($studentMapping->student_email))
                                                <div class="text-xs text-gray-500">{{ $studentMapping->student_email }}</div>
                                            @endif
                                        </td>
                                        
                                        <!-- Lecture Activities -->
                                        @if(isset($lectureActivities))
                                            @foreach($lectureActivities as $activity)
                                                @php
                                                    $gradeRecord = isset($gradeMatrix) ? ($gradeMatrix[$studentMapping->id][$activity->id] ?? null) : null;
                                                @endphp
                                                <td class="px-3 py-4 text-center bg-blue-25 border-l border-blue-100">
                                                    <input 
                                                        type="number" 
                                                        class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 grade-input"
                                                        data-student-mapping-id="{{ $studentMapping->id }}"
                                                        data-activity-id="{{ $activity->id }}"
                                                        data-max-score="{{ $activity->max_score }}"
                                                        value="{{ $gradeRecord?->score ?? '' }}"
                                                        min="0"
                                                        max="{{ $activity->max_score }}"
                                                        step="0.01"
                                                        placeholder="0"
                                                    >
                                                    @if(isset($gradeRecord) && $gradeRecord && isset($gradeRecord->percentage))
                                                        <div class="text-xs text-gray-500 mt-1">{{ number_format($gradeRecord->percentage, 1) }}%</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endif
                                        
                                        <!-- Laboratory Activities -->
                                        @if(isset($labActivities) && $subject->type === 'lecture_lab')
                                            @foreach($labActivities as $activity)
                                                @php
                                                    $gradeRecord = isset($gradeMatrix) ? ($gradeMatrix[$studentMapping->id][$activity->id] ?? null) : null;
                                                @endphp
                                                <td class="px-3 py-4 text-center bg-purple-25 border-l border-purple-100">
                                                    <input 
                                                        type="number" 
                                                        class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-purple-500 focus:border-purple-500 grade-input"
                                                        data-student-mapping-id="{{ $studentMapping->id }}"
                                                        data-activity-id="{{ $activity->id }}"
                                                        data-max-score="{{ $activity->max_score }}"
                                                        value="{{ $gradeRecord?->score ?? '' }}"
                                                        min="0"
                                                        max="{{ $activity->max_score }}"
                                                        step="0.01"
                                                        placeholder="0"
                                                    >
                                                    @if(isset($gradeRecord) && $gradeRecord && isset($gradeRecord->percentage))
                                                        <div class="text-xs text-gray-500 mt-1">{{ number_format($gradeRecord->percentage, 1) }}%</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endif
                                        
                                        <!-- Total CS (Score/Total) -->
                                        <td class="px-6 py-4 text-center bg-teal-25 border-l border-teal-200">
                                            @php
                                                $totalScore = 0;
                                                $totalPossible = 0;
                                                
                                                // Calculate total from lecture activities
                                                if(isset($lectureActivities)) {
                                                    foreach($lectureActivities as $activity) {
                                                        $gradeRecord = isset($gradeMatrix[$studentMapping->id][$activity->id]) ? $gradeMatrix[$studentMapping->id][$activity->id] : null;
                                                        if($gradeRecord && $gradeRecord->score !== null) {
                                                            $totalScore += $gradeRecord->score;
                                                        }
                                                        $totalPossible += $activity->max_score;
                                                    }
                                                }
                                                
                                                // Calculate total from lab activities
                                                if(isset($labActivities) && $subject->type === 'lecture_lab') {
                                                    foreach($labActivities as $activity) {
                                                        $gradeRecord = isset($gradeMatrix[$studentMapping->id][$activity->id]) ? $gradeMatrix[$studentMapping->id][$activity->id] : null;
                                                        if($gradeRecord && $gradeRecord->score !== null) {
                                                            $totalScore += $gradeRecord->score;
                                                        }
                                                        $totalPossible += $activity->max_score;
                                                    }
                                                }
                                            @endphp
                                            <div class="text-sm font-semibold text-teal-900">
                                                {{ number_format($totalScore, 0) }} / {{ number_format($totalPossible, 0) }}
                                            </div>
                                            @if($totalPossible > 0)
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ number_format(($totalScore / $totalPossible) * 100, 1) }}%
                                                </div>
                                            @endif
                                        </td>
                                        
                                        <!-- Class Standing (Auto-calculated) -->
                                        <td class="px-6 py-4 text-center bg-green-25 border-l border-green-200">
                                            <div class="text-sm font-medium text-gray-900" data-student-mapping-id="{{ $studentMapping->id }}">
                                                {{ isset($termGrade) && $termGrade && isset($termGrade->class_standing) ? round($termGrade->class_standing) : '-' }}
                                            </div>
                                        </td>
                                        
                                        <!-- Exam Score (Manual Input) -->
                                        <td class="px-4 py-4 text-center bg-yellow-25 border-l border-yellow-200">
                                            <input 
                                                type="number" 
                                                class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 exam-score-input"
                                                data-student-mapping-id="{{ $studentMapping->id }}"
                                                data-subject-id="{{ $subject->id }}"
                                                data-term="{{ $term }}"
                                                value="{{ isset($termGrade) && $termGrade && isset($termGrade->exam_score) ? $termGrade->exam_score : '' }}"
                                                min="0"
                                                step="0.01"
                                                placeholder="0"
                                            >
                                        </td>
                                        
                                        <!-- Exam Max Score (Manual Input) -->
                                        <td class="px-4 py-4 text-center bg-yellow-25 border-l border-yellow-100">
                                            <input 
                                                type="number" 
                                                class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 exam-max-score-input"
                                                data-student-mapping-id="{{ $studentMapping->id }}"
                                                data-subject-id="{{ $subject->id }}"
                                                data-term="{{ $term }}"
                                                value="{{ isset($termGrade) && $termGrade && isset($termGrade->exam_max_score) ? $termGrade->exam_max_score : 100 }}"
                                                min="1"
                                                step="0.01"
                                                placeholder="100"
                                            >
                                        </td>
                                        
                                        <!-- Exam Grade (Auto-calculated) -->
                                        <td class="px-6 py-4 text-center bg-orange-25 border-l border-orange-200">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ isset($termGrade) && $termGrade && isset($termGrade->exam_grade) ? round($termGrade->exam_grade) : '-' }}
                                            </div>
                                        </td>
                                        
                                        <!-- Term Grade (Auto-calculated) -->
                                        <td class="px-6 py-4 text-center bg-red-25 border-l border-red-200">
                                            <div class="text-sm font-bold text-red-600">
                                                {{ isset($termGrade) && $termGrade && isset($termGrade->term_grade) ? round($termGrade->term_grade) : '-' }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                @if((isset($lectureActivities) && $lectureActivities->isEmpty()) && (isset($labActivities) && $labActivities->isEmpty()))
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    No activities created for <span class="capitalize font-medium">{{ $term }}</span> term. Please add activities first.
                                </p>
                                <div class="mt-2">
                                    <a href="{{ route('activities.create', ['subject_id' => $subject->id]) }}" 
                                       class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition-colors">
                                        <i class="fas fa-plus mr-1"></i>Add Activity
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($subject->gcr_class_id && ((isset($lectureActivities) && $lectureActivities->isNotEmpty() && $lectureActivities->where('gcr_assignment_id', '!=', null)->isEmpty()) || (isset($labActivities) && $labActivities->isNotEmpty() && $labActivities->where('gcr_assignment_id', '!=', null)->isEmpty())))
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fab fa-google text-yellow-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Google Classroom Integration:</strong> Activities exist but none are connected to Google Classroom. Connect your activities to enable grade import.
                                </p>
                                <div class="mt-2">
                                    <a href="{{ route('activities.index', ['subject_id' => $subject->id]) }}" 
                                       class="text-sm bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded transition-colors">
                                        <i class="fas fa-link mr-1"></i>Manage Activities
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Import Confirmation Modal -->
<div id="import-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
                <i class="fab fa-google text-blue-500 text-2xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-gray-900">Import from Google Classroom</h3>
            </div>
        </div>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-3">
                This will import grades from Google Classroom for all activities in the <strong class="capitalize">{{ $term }}</strong> term.
            </p>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Warning:</strong> This may overwrite existing grades. Make sure your activities are properly connected to Google Classroom coursework.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button id="cancel-import" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button id="confirm-import" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fab fa-google mr-2"></i>Import Grades
            </button>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
        <span class="text-gray-700">Saving...</span>
    </div>
</div>
@endsection

@section('styles')
<style>
/* Custom background colors for better visual separation */
.bg-blue-25 { background-color: #f8faff; }
.bg-purple-25 { background-color: #faf9ff; }
.bg-green-25 { background-color: #f7fdf7; }
.bg-yellow-25 { background-color: #fffef7; }
.bg-orange-25 { background-color: #fff9f5; }
.bg-red-25 { background-color: #fef7f7; }

/* Input states */
.grade-input.pending,
.exam-score-input.pending {
    @apply bg-blue-50 border-blue-300;
    position: relative;
}

.grade-input.pending::after,
.exam-score-input.pending::after {
    content: "Press Enter to save";
    position: absolute;
    top: -25px;
    left: 0;
    font-size: 10px;
    color: #08695A;
    background: white;
    padding: 2px 4px;
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    white-space: nowrap;
    z-index: 10;
}

.grade-input.saving,
.exam-score-input.saving {
    @apply bg-yellow-100 border-yellow-400;
}

.grade-input.saved,
.exam-score-input.saved {
    @apply bg-green-100 border-green-400;
}

.grade-input.error,
.exam-score-input.error {
    @apply bg-red-100 border-red-400;
}

/* Sticky column shadow */
.sticky {
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
}

/* Responsive table scrolling */
@media (max-width: 1024px) {
    .min-w-48 { min-width: 12rem; }
    .min-w-32 { min-width: 8rem; }
    .min-w-20 { min-width: 5rem; }
}

@media (max-width: 768px) {
    .min-w-48 { min-width: 10rem; }
    .min-w-32 { min-width: 6rem; }
    .min-w-20 { min-width: 4rem; }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const gradeInputs = document.querySelectorAll('.grade-input');
    const examScoreInputs = document.querySelectorAll('.exam-score-input');
    const loadingOverlay = document.getElementById('loading-overlay');
    let saveTimeouts = new Map(); // Use Map to track individual input timeouts
    let activeRequests = new Set(); // Track active requests to prevent duplicates

    // Handle activity grade inputs
    gradeInputs.forEach(input => {
        const inputKey = `${input.dataset.studentMappingId}-${input.dataset.activityId}`;
        
        // Save only on Enter key or blur (if changed)
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveGrade(this);
            }
        });

        input.addEventListener('blur', function() {
            // Only save if there's been a change and no active request
            if (this.dataset.hasChanged === 'true' && !activeRequests.has(inputKey)) {
                saveGrade(this);
            }
        });

        // Track changes without auto-saving
        input.addEventListener('input', function() {
            this.dataset.hasChanged = 'true';
            // Clear any existing timeout
            if (saveTimeouts.has(inputKey)) {
                clearTimeout(saveTimeouts.get(inputKey));
                saveTimeouts.delete(inputKey);
            }
            // Remove any previous states and add pending state
            this.classList.remove('saved', 'error', 'saving');
            this.classList.add('pending');
        });
    });

    // Handle exam score inputs
    examScoreInputs.forEach(input => {
        const inputKey = `exam-${input.dataset.studentMappingId}-${input.dataset.term}`;
        
        // Save only on Enter key or blur (if changed)
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveExamScore(this);
            }
        });

        input.addEventListener('blur', function() {
            // Only save if there's been a change and no active request
            if (this.dataset.hasChanged === 'true' && !activeRequests.has(inputKey)) {
                saveExamScore(this);
            }
        });

        // Track changes without auto-saving
        input.addEventListener('input', function() {
            this.dataset.hasChanged = 'true';
            // Clear any existing timeout
            if (saveTimeouts.has(inputKey)) {
                clearTimeout(saveTimeouts.get(inputKey));
                saveTimeouts.delete(inputKey);
            }
            // Remove any previous states and add pending state
            this.classList.remove('saved', 'error', 'saving');
            this.classList.add('pending');
        });
    });

    // Handle exam max score inputs
    const examMaxScoreInputs = document.querySelectorAll('.exam-max-score-input');
    examMaxScoreInputs.forEach(input => {
        const inputKey = `exam-max-${input.dataset.studentMappingId}-${input.dataset.term}`;
        
        // Save only on Enter key or blur (if changed)
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Find the corresponding exam score input and save
                const row = this.closest('tr');
                const examScoreInput = row.querySelector('.exam-score-input');
                if (examScoreInput) {
                    saveExamScore(examScoreInput);
                }
            }
        });

        input.addEventListener('blur', function() {
            // Only save if there's been a change
            if (this.dataset.hasChanged === 'true') {
                const row = this.closest('tr');
                const examScoreInput = row.querySelector('.exam-score-input');
                if (examScoreInput) {
                    saveExamScore(examScoreInput);
                }
            }
        });

        // Track changes without auto-saving
        input.addEventListener('input', function() {
            this.dataset.hasChanged = 'true';
            // Clear any existing timeout
            if (saveTimeouts.has(inputKey)) {
                clearTimeout(saveTimeouts.get(inputKey));
                saveTimeouts.delete(inputKey);
            }
            // Remove any previous states and add pending state
            this.classList.remove('saved', 'error', 'saving');
            this.classList.add('pending');
        });
    });

    function saveGrade(input) {
        const studentMappingId = input.dataset.studentMappingId;
        const activityId = input.dataset.activityId;
        const score = input.value || null;
        const maxScore = parseFloat(input.dataset.maxScore);
        const requestKey = `grade-${studentMappingId}-${activityId}`;

        // Prevent duplicate requests
        if (activeRequests.has(requestKey)) {
            console.log('Request already in progress, skipping...');
            return;
        }

        // Validate score
        if (score !== null && parseFloat(score) > maxScore) {
            input.classList.remove('saving');
            input.classList.add('error');
            showNotification(`Score cannot exceed maximum score of ${maxScore}`, 'error');
            return;
        }

        // Add to active requests
        activeRequests.add(requestKey);

        // Add saving state
        input.classList.remove('saved', 'error', 'pending');
        input.classList.add('saving');

        fetch('{{ route("grades.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                student_mapping_id: studentMappingId,
                activity_id: activityId,
                score: score
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Response text:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            activeRequests.delete(requestKey);
            
            if (data.success) {
                input.classList.remove('saving', 'error');
                input.classList.add('saved');
                input.dataset.hasChanged = 'false';
                
                // Update percentage display
                const percentageDiv = input.parentElement.querySelector('.text-xs');
                if (data.grade_record && data.grade_record.percentage !== null) {
                    if (percentageDiv) {
                        percentageDiv.textContent = data.grade_record.percentage.toFixed(1) + '%';
                    } else {
                        const newPercentageDiv = document.createElement('div');
                        newPercentageDiv.className = 'text-xs text-gray-500 mt-1';
                        newPercentageDiv.textContent = data.grade_record.percentage.toFixed(1) + '%';
                        input.parentElement.appendChild(newPercentageDiv);
                    }
                } else if (percentageDiv) {
                    percentageDiv.remove();
                }
                
                // Remove saved state after 1.5 seconds
                setTimeout(() => {
                    input.classList.remove('saved');
                }, 1500);
                
                showNotification('Grade saved successfully', 'success');
            } else {
                input.classList.remove('saving');
                input.classList.add('error');
                input.dataset.hasChanged = 'false';
                showNotification(data.error || 'Failed to save grade', 'error');
            }
        })
        .catch(error => {
            activeRequests.delete(requestKey);
            input.classList.remove('saving');
            input.classList.add('error');
            input.dataset.hasChanged = 'false';
            console.error('Error saving grade:', error);
            
            let errorMessage = 'Network error. Please check your connection.';
            if (error.message.includes('HTTP')) {
                errorMessage = `Server error: ${error.message}`;
            } else if (error.name === 'TypeError') {
                errorMessage = 'Connection failed. Please check if the server is running.';
            }
            
            showNotification(errorMessage, 'error');
        });
    }

    function saveExamScore(input) {
        const studentMappingId = input.dataset.studentMappingId;
        const subjectId = input.dataset.subjectId;
        const term = input.dataset.term;
        const examScore = input.value || null;
        const requestKey = `exam-${studentMappingId}-${term}`;

        // Get the exam max score from the adjacent input
        const row = input.closest('tr');
        const examMaxScoreInput = row.querySelector('.exam-max-score-input');
        const examMaxScore = examMaxScoreInput ? (examMaxScoreInput.value || 100) : 100;

        // Prevent duplicate requests
        if (activeRequests.has(requestKey)) {
            console.log('Exam score request already in progress, skipping...');
            return;
        }

        // Validate exam score
        if (examScore !== null && parseFloat(examScore) < 0) {
            input.classList.remove('saving');
            input.classList.add('error');
            showNotification('Exam score cannot be negative', 'error');
            return;
        }

        if (examScore !== null && parseFloat(examScore) > parseFloat(examMaxScore)) {
            input.classList.remove('saving');
            input.classList.add('error');
            showNotification(`Exam score cannot exceed max score of ${examMaxScore}`, 'error');
            return;
        }

        // Add to active requests
        activeRequests.add(requestKey);
        
        // Add saving state
        input.classList.remove('saved', 'error', 'pending');
        input.classList.add('saving');

        fetch('{{ route("grades.update-exam-score") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                student_mapping_id: studentMappingId,
                subject_id: subjectId,
                term: term,
                exam_score: examScore,
                exam_max_score: examMaxScore
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            activeRequests.delete(requestKey);
            
            if (data.success) {
                input.classList.remove('saving', 'error');
                input.classList.add('saved');
                input.dataset.hasChanged = 'false';
                
                // Update computed grades in the same row
                const row = input.closest('tr');
                const examGradeCell = row.querySelector('td:nth-last-child(2) div');
                const termGradeCell = row.querySelector('td:last-child div');
                
                if (data.term_grade && data.term_grade.exam_grade !== null) {
                    examGradeCell.textContent = Math.round(data.term_grade.exam_grade);
                } else {
                    examGradeCell.textContent = '-';
                }
                
                if (data.term_grade && data.term_grade.term_grade !== null) {
                    termGradeCell.textContent = Math.round(data.term_grade.term_grade);
                } else {
                    termGradeCell.textContent = '-';
                }
                
                // Remove saved state after 1.5 seconds
                setTimeout(() => {
                    input.classList.remove('saved');
                }, 1500);
                
                showNotification('Exam score saved successfully', 'success');
            } else {
                input.classList.remove('saving');
                input.classList.add('error');
                input.dataset.hasChanged = 'false';
                showNotification(data.error || 'Failed to save exam score', 'error');
            }
        })
        .catch(error => {
            activeRequests.delete(requestKey);
            input.classList.remove('saving');
            input.classList.add('error');
            input.dataset.hasChanged = 'false';
            console.error('Error saving exam score:', error);
            showNotification(`Failed to save exam score: ${error.message}`, 'error');
        });
    }

    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Handle Google Classroom import
    const importBtn = document.getElementById('import-grades-btn');
    const importModal = document.getElementById('import-modal');
    const cancelImportBtn = document.getElementById('cancel-import');
    const confirmImportBtn = document.getElementById('confirm-import');
    
    if (importBtn) {
        importBtn.addEventListener('click', function() {
            importModal.classList.remove('hidden');
        });
    }
    
    if (cancelImportBtn) {
        cancelImportBtn.addEventListener('click', function() {
            importModal.classList.add('hidden');
        });
    }
    
    if (confirmImportBtn) {
        confirmImportBtn.addEventListener('click', function() {
            importModal.classList.add('hidden');
            importGradesFromClassroom();
        });
    }
    
    // Close modal when clicking outside
    if (importModal) {
        importModal.addEventListener('click', function(e) {
            if (e.target === importModal) {
                importModal.classList.add('hidden');
            }
        });
    }

    function importGradesFromClassroom() {
        const importBtn = document.getElementById('import-grades-btn');
        const originalText = importBtn.innerHTML;
        
        // Show loading state
        importBtn.disabled = true;
        importBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...';
        loadingOverlay.classList.remove('hidden');

        fetch('{{ route("grades.import-from-classroom", $subject) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                term: '{{ $term }}'
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Reload the page to show updated grades
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Failed to import grades', 'error');
            }
        })
        .catch(error => {
            console.error('Error importing grades:', error);
            let errorMessage = 'Failed to import grades from Google Classroom.';
            if (error.message.includes('HTTP')) {
                errorMessage = `Server error: ${error.message}`;
            } else if (error.name === 'TypeError') {
                errorMessage = 'Connection failed. Please check if the server is running.';
            }
            showNotification(errorMessage, 'error');
        })
        .finally(() => {
            // Reset button state
            importBtn.disabled = false;
            importBtn.innerHTML = originalText;
            loadingOverlay.classList.add('hidden');
        });
    }
});

// Save Term Configuration
function saveTermConfig() {
    const config = Alpine.raw(this.termConfig);
    
    // Validate total equals 100
    const total = parseFloat(config.class_standing_weight) + parseFloat(config.exam_weight);
    if (total !== 100) {
        showNotification('Class Standing and Exam weights must sum to 100%', 'error');
        return;
    }

    // Collect activity weights
    const activityWeights = {};
    document.querySelectorAll('input[name^="activity_weights"]').forEach(input => {
        const activityId = input.name.match(/\[(\d+)\]/)[1];
        activityWeights[activityId] = parseFloat(input.value) || 0;
    });

    fetch('{{ route("grades.save-grading-config", $subject) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            term: '{{ $term }}',
            class_standing_weight: config.class_standing_weight,
            exam_weight: config.exam_weight,
            activity_weights: activityWeights
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            this.showConfig = false;
            // Reload to show updated weights
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(data.message || 'Failed to save configuration', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving configuration:', error);
        showNotification('Failed to save configuration', 'error');
    });
}

// Save Final Rating Configuration
function saveFinalConfig() {
    const config = Alpine.raw(this.finalConfig);
    
    // Validate total equals 100
    const total = parseFloat(config.prelim_weight) + parseFloat(config.midterm_weight) + parseFloat(config.finals_weight);
    if (total !== 100) {
        showNotification('Term weights must sum to 100%', 'error');
        return;
    }

    fetch('{{ route("grades.save-final-rating-config", $subject) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            prelim_weight: config.prelim_weight,
            midterm_weight: config.midterm_weight,
            finals_weight: config.finals_weight
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            this.showFinalConfig = false;
        } else {
            showNotification(data.message || 'Failed to save configuration', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving configuration:', error);
        showNotification('Failed to save configuration', 'error');
    });
}
</script>
@endsection