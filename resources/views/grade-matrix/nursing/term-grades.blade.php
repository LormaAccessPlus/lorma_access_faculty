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
                <a href="{{ route('grade-matrix.nursing.matrix', $subject) }}" class="text-white px-4 py-2 rounded-lg font-medium transition-colors"
                   style="background-color: #08695A;"
                   onmouseover="this.style.backgroundColor='#065A4A';"
                   onmouseout="this.style.backgroundColor='#08695A';">
                    <i class="fas fa-table mr-2"></i>Full Matrix
                </a>
                <a href="{{ route('grade-matrix.nursing') }}" class="px-4 py-2 rounded-lg font-medium transition-colors border"
                   style="border-color: #08695A; color: #08695A; background-color: white;"
                   onmouseover="this.style.backgroundColor='#08695A'; this.style.color='white';"
                   onmouseout="this.style.backgroundColor='white'; this.style.color='#08695A';">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Nursing Matrix
                </a>
            </div>
        </div>
    </div>

    <!-- Term Navigation Tabs -->
    <div class="bg-white rounded-lg shadow-sm border mb-6" x-data="{ 
        termProgress: {
            prelim: {{ $termProgress['prelim']['percentage'] ?? 0 }},
            midterm: {{ $termProgress['midterm']['percentage'] ?? 0 }},
            finals: {{ $termProgress['finals']['percentage'] ?? 0 }}
        }
    }">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                @foreach(['prelim', 'midterm', 'finals'] as $termKey)
                    <a href="{{ route('grade-matrix.nursing.term', ['subject' => $subject, 'term' => $termKey]) }}"
                       class="py-4 px-4 border-b-2 font-medium text-sm transition-colors {{ $term === $termKey ? 'text-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                       style="{{ $term === $termKey ? 'border-color: #08695A; background-color: #08695A; border-radius: 8px 8px 0 0; margin-bottom: -1px;' : '' }}">
                        <span class="capitalize">{{ $termKey }}</span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="termProgress.{{ $termKey }} == 100 ? 'bg-green-100 text-green-800' : (termProgress.{{ $termKey }} > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')"
                              x-text="termProgress.{{ $termKey }} + '%'">
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Nursing Grading Formula Display -->
        <div class="mb-6 p-5 bg-gradient-to-r from-teal-50 to-cyan-50 rounded-lg border-2 border-teal-300">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                <i class="fas fa-calculator mr-2 text-teal-600"></i>
                Nursing Grading Formula (All Terms)
            </h4>
            
            <div class="bg-white rounded-lg p-4 border border-teal-200">
                <div class="space-y-3 text-sm text-gray-700">
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded">
                        <span class="font-semibold">Activities:</span>
                        <span class="font-mono text-blue-700">(score/total) × 60 + 40 × 15%</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded">
                        <span class="font-semibold">Quizzes:</span>
                        <span class="font-mono text-green-700">(score/total) × 60 + 40 × 25%</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded">
                        <span class="font-semibold">Exam:</span>
                        <span class="font-mono text-purple-700">(score/total) × 60 + 40 × 60%</span>
                    </div>
                </div>
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
                            <a href="{{ route('activities.index', ['subject_id' => $subject->id]) }}" 
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

                <!-- Activities, Quizzes, and Exam Scores Table - NURSING SPECIFIC -->
                <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            @php
                                // Separate activities and quizzes for nursing matrix
                                $activities = isset($lectureActivities) ? $lectureActivities->where('activity_category', 'activity') : collect();
                                $quizzes = isset($lectureActivities) ? $lectureActivities->where('activity_category', 'quiz') : collect();
                                
                                // Include lab activities/quizzes if applicable
                                if(isset($labActivities) && $subject->type === 'lecture_lab') {
                                    $activities = $activities->merge($labActivities->where('activity_category', 'activity'));
                                    $quizzes = $quizzes->merge($labActivities->where('activity_category', 'quiz'));
                                }
                            @endphp
                            
                            <!-- First Header Row: Group Headers -->
                            <tr>
                                <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100 sticky left-0 z-10 min-w-48 border-r border-gray-300">
                                    Student Name
                                </th>
                                
                                <!-- Activities Group Header -->
                                @if($activities->isNotEmpty())
                                    <th colspan="{{ $activities->count() }}" class="px-4 py-2 text-center text-xs font-medium text-blue-700 uppercase tracking-wider bg-blue-100 border-l border-blue-300">
                                        <i class="fas fa-tasks mr-1"></i>Activities
                                    </th>
                                @endif
                                
                                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider bg-blue-100 border-l border-blue-300 min-w-32">
                                    Total<br>Activities
                                </th>
                                
                                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider bg-blue-200 border-l border-blue-400 min-w-32">
                                    Activities<br>Grade
                                </th>
                                
                                <!-- Quizzes Group Header -->
                                @if($quizzes->isNotEmpty())
                                    <th colspan="{{ $quizzes->count() }}" class="px-4 py-2 text-center text-xs font-medium text-purple-700 uppercase tracking-wider bg-purple-100 border-l border-purple-300">
                                        <i class="fas fa-question-circle mr-1"></i>Quizzes
                                    </th>
                                @endif
                                
                                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider bg-purple-100 border-l border-purple-300 min-w-32">
                                    Total<br>Quizzes
                                </th>
                                
                                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider bg-purple-200 border-l border-purple-400 min-w-32">
                                    Quizzes<br>Grade
                                </th>
                                
                                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider bg-yellow-50 border-l border-yellow-200 min-w-32">
                                    Exam<br>Score
                                </th>
                                
                                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider bg-yellow-100 border-l border-yellow-300 min-w-32">
                                    Exam<br>Grade
                                </th>
                                
                                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider bg-orange-100 border-l border-orange-300 min-w-32">
                                    <span class="capitalize">{{ $term }}</span><br>Grade
                                </th>
                            </tr>
                            
                            <!-- Second Header Row: Individual Columns -->
                            <tr>
                                <!-- Individual Activity Columns -->
                                @foreach($activities as $activity)
                                    <th class="px-3 py-2 text-center text-xs font-medium text-blue-600 bg-blue-50 border-l border-blue-200 min-w-24" title="{{ $activity->name }}">
                                        <div class="flex flex-col items-center">
                                            <span class="font-semibold">{{ Str::limit($activity->name, 10) }}</span>
                                            <span class="text-xs text-gray-500 mt-1">/{{ $activity->max_score == floor($activity->max_score) ? intval($activity->max_score) : $activity->max_score }}</span>
                                        </div>
                                    </th>
                                @endforeach
                                
                                <!-- Individual Quiz Columns -->
                                @foreach($quizzes as $quiz)
                                    <th class="px-3 py-2 text-center text-xs font-medium text-purple-600 bg-purple-50 border-l border-purple-200 min-w-24" title="{{ $quiz->name }}">
                                        <div class="flex flex-col items-center">
                                            <span class="font-semibold">{{ Str::limit($quiz->name, 10) }}</span>
                                            <span class="text-xs text-gray-500 mt-1">/{{ $quiz->max_score == floor($quiz->max_score) ? intval($quiz->max_score) : $quiz->max_score }}</span>
                                        </div>
                                    </th>
                                @endforeach
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
                                            <div class="text-sm font-medium text-gray-900">{{ $studentMapping->formatted_name }}</div>
                                            @if(isset($studentMapping->student_email))
                                                <div class="text-xs text-gray-500">{{ $studentMapping->student_email }}</div>
                                            @endif
                                        </td>
                                        
                                        <!-- Activities (15%) -->
                                        @foreach($activities as $activity)
                                            @php
                                                $gradeRecord = isset($gradeMatrix) ? ($gradeMatrix[$studentMapping->id][$activity->id] ?? null) : null;
                                            @endphp
                                            <td class="px-3 py-4 text-center bg-blue-25 border-l border-blue-100">
                                                <input 
                                                    type="number" 
                                                    class="w-16 px-2 py-1 text-center text-sm border rounded focus:ring-2 grade-input {{ $activity->gcr_assignment_id ? 'bg-gray-100 cursor-not-allowed border-gray-200' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}"
                                                    data-student-mapping-id="{{ $studentMapping->id }}"
                                                    data-activity-id="{{ $activity->id }}"
                                                    data-max-score="{{ $activity->max_score }}"
                                                    value="{{ $gradeRecord && $gradeRecord->score !== null ? ($gradeRecord->score == floor($gradeRecord->score) ? intval($gradeRecord->score) : $gradeRecord->score) : '' }}"
                                                    min="0"
                                                    max="{{ $activity->max_score }}"
                                                    step="any"
                                                    placeholder="0"
                                                    @if($activity->gcr_assignment_id) readonly title="This grade is synced from Google Classroom and cannot be edited manually" @endif
                                                >
                                                @if(isset($gradeRecord) && $gradeRecord && isset($gradeRecord->percentage))
                                                    <div class="text-xs text-gray-500 mt-1">{{ number_format($gradeRecord->percentage, 2) }}%</div>
                                                @endif
                                            </td>
                                        @endforeach
                                        
                                        @php
                                            // Calculate activities total - sum all activity scores
                                            $activitiesTotal = 0;
                                            $activitiesMax = 0;
                                            
                                            foreach($activities as $activity) {
                                                $gradeRecord = isset($gradeMatrix[$studentMapping->id][$activity->id]) ? $gradeMatrix[$studentMapping->id][$activity->id] : null;
                                                if($gradeRecord && $gradeRecord->score !== null) {
                                                    $activitiesTotal += floatval($gradeRecord->score);
                                                }
                                                $activitiesMax += floatval($activity->max_score);
                                            }
                                            
                                            // Nursing formula: (Total Score / Max Score × 60 + 40) × 0.15
                                            $activitiesScore = $activitiesMax > 0 ? (($activitiesTotal / $activitiesMax) * 60 + 40) * 0.15 : 0;
                                        @endphp
                                        
                                        <!-- Total Activities -->
                                        <td class="px-6 py-4 text-center bg-blue-50 border-l border-blue-300">
                                            <div class="text-lg font-bold text-blue-800">
                                                {{ number_format($activitiesTotal, 0) }}/{{ number_format($activitiesMax, 0) }}
                                            </div>
                                            @if($activitiesMax > 0)
                                                <div class="text-xs text-gray-600 mt-1">
                                                    {{ number_format(($activitiesTotal / $activitiesMax) * 100, 2) }}%
                                                </div>
                                            @endif
                                        </td>
                                        
                                        <!-- Activities Grade -->
                                        <td class="px-6 py-4 text-center bg-blue-200 border-l border-blue-400">
                                            <div class="text-xl font-bold text-blue-900">
                                                {{ number_format($activitiesScore, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1">
                                                15% weight
                                            </div>
                                        </td>
                                        
                                        <!-- Quizzes (25%) -->
                                        @foreach($quizzes as $quiz)
                                            @php
                                                $gradeRecord = isset($gradeMatrix) ? ($gradeMatrix[$studentMapping->id][$quiz->id] ?? null) : null;
                                            @endphp
                                            <td class="px-3 py-4 text-center bg-purple-25 border-l border-purple-100">
                                                <input 
                                                    type="number" 
                                                    class="w-16 px-2 py-1 text-center text-sm border rounded focus:ring-2 grade-input {{ $quiz->gcr_assignment_id ? 'bg-gray-100 cursor-not-allowed border-gray-200' : 'border-gray-300 focus:ring-purple-500 focus:border-purple-500' }}"
                                                    data-student-mapping-id="{{ $studentMapping->id }}"
                                                    data-activity-id="{{ $quiz->id }}"
                                                    data-max-score="{{ $quiz->max_score }}"
                                                    value="{{ $gradeRecord && $gradeRecord->score !== null ? ($gradeRecord->score == floor($gradeRecord->score) ? intval($gradeRecord->score) : $gradeRecord->score) : '' }}"
                                                    min="0"
                                                    max="{{ $quiz->max_score }}"
                                                    step="any"
                                                    placeholder="0"
                                                    @if($quiz->gcr_assignment_id) readonly title="This grade is synced from Google Classroom and cannot be edited manually" @endif
                                                >
                                                @if(isset($gradeRecord) && $gradeRecord && isset($gradeRecord->percentage))
                                                    <div class="text-xs text-gray-500 mt-1">{{ number_format($gradeRecord->percentage, 2) }}%</div>
                                                @endif
                                            </td>
                                        @endforeach
                                        
                                        @php
                                            // Calculate quizzes total - sum all quiz scores
                                            $quizzesTotal = 0;
                                            $quizzesMax = 0;
                                            
                                            foreach($quizzes as $quiz) {
                                                $gradeRecord = isset($gradeMatrix[$studentMapping->id][$quiz->id]) ? $gradeMatrix[$studentMapping->id][$quiz->id] : null;
                                                if($gradeRecord && $gradeRecord->score !== null) {
                                                    $quizzesTotal += floatval($gradeRecord->score);
                                                }
                                                $quizzesMax += floatval($quiz->max_score);
                                            }
                                            
                                            // Nursing formula: (Total Score / Max Score × 60 + 40) × 0.25
                                            $quizzesScore = $quizzesMax > 0 ? (($quizzesTotal / $quizzesMax) * 60 + 40) * 0.25 : 0;
                                        @endphp
                                        
                                        <!-- Total Quizzes -->
                                        <td class="px-6 py-4 text-center bg-purple-50 border-l border-purple-300">
                                            <div class="text-lg font-bold text-purple-800">
                                                {{ number_format($quizzesTotal, 0) }}/{{ number_format($quizzesMax, 0) }}
                                            </div>
                                            @if($quizzesMax > 0)
                                                <div class="text-xs text-gray-600 mt-1">
                                                    {{ number_format(($quizzesTotal / $quizzesMax) * 100, 2) }}%
                                                </div>
                                            @endif
                                        </td>
                                        
                                        <!-- Quizzes Grade -->
                                        <td class="px-6 py-4 text-center bg-purple-200 border-l border-purple-400">
                                            <div class="text-xl font-bold text-purple-900">
                                                {{ number_format($quizzesScore, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1">
                                                25% weight
                                            </div>
                                        </td>
                                        
                                        <!-- Exam Score (Manual Input) -->
                                        <td class="px-6 py-4 text-center bg-yellow-25 border-l border-yellow-200">
                                            <div class="flex items-center justify-center gap-1">
                                                <input 
                                                    type="number" 
                                                    class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 exam-score-input"
                                                    data-student-mapping-id="{{ $studentMapping->id }}"
                                                    data-subject-id="{{ $subject->id }}"
                                                    data-term="{{ $term }}"
                                                    value="{{ isset($termGrade) && $termGrade && $termGrade->exam_score !== null ? ($termGrade->exam_score == floor($termGrade->exam_score) ? intval($termGrade->exam_score) : $termGrade->exam_score) : '' }}"
                                                    min="0"
                                                    step="any"
                                                    placeholder="0"
                                                >
                                                <span class="text-gray-500">/</span>
                                                <input 
                                                    type="number" 
                                                    class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 exam-max-score-input"
                                                    data-student-mapping-id="{{ $studentMapping->id }}"
                                                    data-subject-id="{{ $subject->id }}"
                                                    data-term="{{ $term }}"
                                                    value="{{ isset($termGrade) && $termGrade && $termGrade->exam_max_score !== null ? ($termGrade->exam_max_score == floor($termGrade->exam_max_score) ? intval($termGrade->exam_max_score) : $termGrade->exam_max_score) : 100 }}"
                                                    min="1"
                                                    step="any"
                                                    placeholder="100"
                                                    title="Press Enter to save"
                                                >
                                            </div>
                                        </td>
                                        
                                        <!-- Exam Grade (Calculated using nursing formula: (Score/Total × 60 + 40) × 0.60) -->
                                        <td class="px-6 py-4 text-center bg-yellow-100 border-l border-yellow-300">
                                            @php
                                                $examScore = isset($termGrade) && $termGrade && $termGrade->exam_score !== null ? $termGrade->exam_score : 0;
                                                $examMaxScore = isset($termGrade) && $termGrade && $termGrade->exam_max_score !== null ? $termGrade->exam_max_score : 100;
                                                
                                                // Nursing formula: (Score/Total × 60 + 40) × 0.60
                                                $examGrade = $examMaxScore > 0 ? (($examScore / $examMaxScore) * 60 + 40) * 0.60 : 0;
                                            @endphp
                                            <div class="text-lg font-bold text-yellow-900">
                                                {{ number_format($examGrade, 2) }}
                                            </div>
                                            @if($examScore > 0)
                                                <div class="text-xs text-gray-600 mt-1">
                                                    {{ $examScore }}/{{ $examMaxScore }}
                                                </div>
                                            @endif
                                        </td>
                                        
                                        <!-- Term Grade (Activities + Quizzes + Exam) -->
                                        <td class="px-6 py-4 text-center bg-orange-100 border-l border-orange-300">
                                            @php
                                                $termGradeTotal = $activitiesScore + $quizzesScore + $examGrade;
                                            @endphp
                                            <div class="text-xl font-bold text-orange-900">
                                                {{ number_format($termGradeTotal, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1">
                                                @if($termGradeTotal >= 75)
                                                    <span class="text-green-600 font-semibold">
                                                        <i class="fas fa-check-circle mr-1"></i>Passing
                                                    </span>
                                                @else
                                                    <span class="text-red-600 font-semibold">
                                                        <i class="fas fa-times-circle mr-1"></i>Failing
                                                    </span>
                                                @endif
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
                saveExamScore(this.closest('td').querySelector('.exam-score-input'));
            }
        });

        input.addEventListener('blur', function() {
            // Save the exam score when max score changes
            const examScoreInput = this.closest('td').querySelector('.exam-score-input');
            if (examScoreInput) {
                saveExamScore(examScoreInput);
            }
        });

        // Track changes
        input.addEventListener('input', function() {
            this.dataset.hasChanged = 'true';
            this.classList.remove('saved', 'error', 'saving');
            this.classList.add('pending');
        });
    });

    // Add input event listener to all activity grade inputs for dynamic calculation
    document.querySelectorAll('.activity-grade-input').forEach(input => {
        input.addEventListener('input', function() {
            updateNursingTotals(this);
        });
    });
    
    // Function to dynamically update nursing totals (activities and quizzes separately)
    function updateNursingTotals(input) {
        const row = input.closest('tr');
        
        // Get all activity inputs (not quizzes)
        const activityInputs = Array.from(row.querySelectorAll('.grade-input')).filter(inp => {
            // Check if this input is in an activity column (bg-blue-25) vs quiz column (bg-purple-25)
            const cell = inp.closest('td');
            return cell && cell.classList.contains('bg-blue-25');
        });
        
        // Get all quiz inputs
        const quizInputs = Array.from(row.querySelectorAll('.grade-input')).filter(inp => {
            const cell = inp.closest('td');
            return cell && cell.classList.contains('bg-purple-25');
        });
        
        // Calculate activities total
        let activitiesTotal = 0;
        let activitiesMax = 0;
        activityInputs.forEach(activityInput => {
            const score = parseFloat(activityInput.value) || 0;
            const maxScore = parseFloat(activityInput.dataset.maxScore) || 0;
            activitiesTotal += score;
            activitiesMax += maxScore;
        });
        
        // Calculate quizzes total
        let quizzesTotal = 0;
        let quizzesMax = 0;
        quizInputs.forEach(quizInput => {
            const score = parseFloat(quizInput.value) || 0;
            const maxScore = parseFloat(quizInput.dataset.maxScore) || 0;
            quizzesTotal += score;
            quizzesMax += maxScore;
        });
        
        // Update Total Activities display (bg-blue-50)
        const totalActivitiesCell = row.querySelector('td.bg-blue-50');
        if (totalActivitiesCell) {
            const scoreDisplay = totalActivitiesCell.querySelector('.text-lg');
            const percentageDisplay = totalActivitiesCell.querySelector('.text-xs');
            
            if (scoreDisplay) {
                scoreDisplay.textContent = `${Math.round(activitiesTotal)}/${Math.round(activitiesMax)}`;
            }
            if (percentageDisplay && activitiesMax > 0) {
                // Nursing transmuted formula: (total/max × 60 + 40)
                const transmutedScore = (activitiesTotal / activitiesMax) * 60 + 40;
                percentageDisplay.textContent = `${transmutedScore.toFixed(2)}%`;
            }
        }
        
        // Update Total Quizzes display (bg-purple-50)
        const totalQuizzesCell = row.querySelector('td.bg-purple-50');
        if (totalQuizzesCell) {
            const scoreDisplay = totalQuizzesCell.querySelector('.text-lg');
            const percentageDisplay = totalQuizzesCell.querySelector('.text-xs');
            
            if (scoreDisplay) {
                scoreDisplay.textContent = `${Math.round(quizzesTotal)}/${Math.round(quizzesMax)}`;
            }
            if (percentageDisplay && quizzesMax > 0) {
                // Nursing transmuted formula: (total/max × 60 + 40)
                const transmutedScore = (quizzesTotal / quizzesMax) * 60 + 40;
                percentageDisplay.textContent = `${transmutedScore.toFixed(2)}%`;
            }
        }
    }

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

        console.log('Saving grade:', { studentMappingId, activityId, score, maxScore });
        console.log('Route URL:', '{{ route("grades.update") }}');
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        console.log('CSRF Token exists:', !!csrfToken);
        console.log('CSRF Token value:', csrfToken ? csrfToken.getAttribute('content') : 'MISSING');
        
        // Check if CSRF token is missing
        if (!csrfToken || !csrfToken.getAttribute('content')) {
            activeRequests.delete(requestKey);
            input.classList.remove('saving');
            input.classList.add('error');
            showNotification('Security token missing. Please refresh the page.', 'error');
            return;
        }
        
        // Create abort controller for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
        
        fetch('{{ route("grades.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                student_mapping_id: studentMappingId,
                activity_id: activityId,
                score: score,
                matrix_type: 'nursing'
            }),
            signal: controller.signal
        })
        .finally(() => clearTimeout(timeoutId))
        .then(response => {
            console.log('Response received:', response.status, response.statusText);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error response:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
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
                if (data.grade_record && data.grade_record.percentage !== null && data.grade_record.percentage !== undefined) {
                    const percentage = parseFloat(data.grade_record.percentage);
                    if (!isNaN(percentage)) {
                        if (percentageDiv) {
                            percentageDiv.textContent = percentage.toFixed(2) + '%';
                        } else {
                            const newPercentageDiv = document.createElement('div');
                            newPercentageDiv.className = 'text-xs text-gray-500 mt-1';
                            newPercentageDiv.textContent = percentage.toFixed(2) + '%';
                            input.parentElement.appendChild(newPercentageDiv);
                        }
                    }
                } else if (percentageDiv) {
                    percentageDiv.remove();
                }
                
                // Recalculate totals for nursing
                updateNursingTotals(input);
                
                // Reload class standing from server after a short delay to ensure backend processing is complete
                setTimeout(() => {
                    reloadClassStanding(input.closest('tr'));
                }, 300);
                
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
            console.error('Error details:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            
            let errorMessage = 'Failed to save grade. ';
            if (error.name === 'AbortError') {
                errorMessage += 'Request timeout - server took too long to respond.';
            } else if (error.message.includes('HTTP')) {
                errorMessage += error.message;
            } else if (error.name === 'TypeError' || error.message.includes('Failed to fetch')) {
                errorMessage += 'Network error - please check your connection or try refreshing the page.';
            } else {
                errorMessage += error.message;
            }
            
            showNotification(errorMessage, 'error');
        });
    }

    function reloadClassStanding(row) {
        const studentMappingId = row.querySelector('.grade-input')?.dataset.studentMappingId;
        const subjectId = '{{ $subject->id }}';
        const term = '{{ $term }}';
        
        if (!studentMappingId) {
            console.warn('No student mapping ID found');
            return;
        }
        
        console.log('Reloading grades for student:', studentMappingId);
        
        fetch(`/grades/get-term-grade?student_mapping_id=${studentMappingId}&subject_id=${subjectId}&term=${term}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received grade data:', data);
            
            // For nursing matrix: Update Activities Grade (bg-blue-200, text-xl)
            const activitiesGradeCell = row.querySelector('td.bg-blue-200 .text-xl');
            console.log('Activities grade cell found:', !!activitiesGradeCell);
            console.log('Activities score from data:', data.activities_score);
            console.log('Activities grade cell element:', activitiesGradeCell);
            
            if (activitiesGradeCell) {
                if (data.activities_score !== null && data.activities_score !== undefined) {
                    const newValue = parseFloat(data.activities_score).toFixed(2);
                    console.log('Setting activities grade from', activitiesGradeCell.textContent, 'to', newValue);
                    activitiesGradeCell.textContent = newValue;
                    console.log('Activities grade cell now shows:', activitiesGradeCell.textContent);
                } else {
                    console.warn('Activities score is null or undefined, keeping current value');
                    // Don't change the value if data is not available
                }
            } else {
                console.warn('Activities grade cell not found - trying alternate selector');
                // Try finding by different selector
                const allBlueCells = row.querySelectorAll('td.bg-blue-200');
                console.log('Found blue-200 cells:', allBlueCells.length);
                allBlueCells.forEach((cell, index) => {
                    console.log(`Blue cell ${index}:`, cell.innerHTML);
                });
            }
            
            // For nursing matrix: Update Quizzes Grade (bg-purple-200, text-xl)
            const quizzesGradeCell = row.querySelector('td.bg-purple-200 .text-xl');
            console.log('Quizzes grade cell found:', !!quizzesGradeCell);
            console.log('Quizzes score from data:', data.quizzes_score);
            
            if (quizzesGradeCell) {
                if (data.quizzes_score !== null && data.quizzes_score !== undefined) {
                    quizzesGradeCell.textContent = parseFloat(data.quizzes_score).toFixed(2);
                    console.log('Updated quizzes grade to:', data.quizzes_score);
                } else {
                    console.warn('Quizzes score is null or undefined, keeping current value');
                    // Don't change the value if data is not available
                }
            } else {
                console.warn('Quizzes grade cell not found');
            }
            
            // For non-nursing: Update Class Standing cell (bg-green-25)
            const classStandingCell = row.querySelector('td.bg-green-25 .text-sm');
            if (classStandingCell && data.class_standing !== null && data.class_standing !== undefined) {
                classStandingCell.textContent = parseFloat(data.class_standing).toFixed(2);
            }
            
            // Update Exam Grade cell (bg-yellow-100 for nursing, text-xl)
            const examGradeCell = row.querySelector('td.bg-yellow-100 .text-xl');
            if (examGradeCell) {
                if (data.exam_grade !== null && data.exam_grade !== undefined) {
                    examGradeCell.textContent = parseFloat(data.exam_grade).toFixed(2);
                    console.log('Updated exam grade to:', data.exam_grade);
                }
            }
            
            // Update Term Grade cell (bg-orange-100 for nursing, text-xl)
            const termGradeCell = row.querySelector('td.bg-orange-100 .text-xl');
            if (termGradeCell) {
                if (data.term_grade !== null && data.term_grade !== undefined) {
                    termGradeCell.textContent = parseFloat(data.term_grade).toFixed(2);
                    console.log('Updated term grade to:', data.term_grade);
                }
            }
            
            // Update term progress after grades are updated
            updateTermProgress();
        })
        .catch(error => {
            console.error('Error reloading grades:', error);
        });
    }

    function updateTermProgress() {
        const totalStudents = document.querySelectorAll('tbody tr').length;
        if (totalStudents === 0) return;
        
        // Count students with class standing (non-empty and not "-")
        const studentsWithGrades = Array.from(document.querySelectorAll('tbody tr')).filter(row => {
            const classStandingCell = row.querySelector('td.bg-green-25 .text-sm');
            const classStanding = classStandingCell?.textContent.trim();
            return classStanding && classStanding !== '-' && classStanding !== '0.00';
        }).length;
        
        const percentage = Math.round((studentsWithGrades / totalStudents) * 100);
        
        // Update the Alpine.js data
        const termTabsContainer = document.querySelector('[x-data*="termProgress"]');
        if (termTabsContainer && termTabsContainer.__x) {
            termTabsContainer.__x.$data.termProgress['{{ $term }}'] = percentage;
        }
    }

    function saveExamScore(input) {
        const studentMappingId = input.dataset.studentMappingId;
        const subjectId = input.dataset.subjectId;
        const term = input.dataset.term;
        const examScore = input.value || null;
        const requestKey = `exam-${studentMappingId}-${term}`;

        // Get max score from the adjacent input field
        const examMaxScoreInput = input.closest('td').querySelector('.exam-max-score-input');
        const examMaxScore = examMaxScoreInput ? parseFloat(examMaxScoreInput.value) || 100 : 100;

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
                exam_max_score: examMaxScore,
                matrix_type: 'nursing'
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
                const classStandingCell = row.querySelector('td.bg-green-25 div');
                const examGradeCell = row.querySelector('td:nth-last-child(2) div');
                const termGradeCell = row.querySelector('td:last-child div');
                
                // Update class standing with 2 decimals
                if (data.term_grade && data.term_grade.class_standing !== null && data.term_grade.class_standing !== undefined) {
                    classStandingCell.textContent = parseFloat(data.term_grade.class_standing).toFixed(2);
                }
                
                // Update exam grade with 2 decimals
                if (data.term_grade && data.term_grade.exam_grade !== null) {
                    examGradeCell.textContent = parseFloat(data.term_grade.exam_grade).toFixed(2);
                } else {
                    examGradeCell.textContent = '-';
                }
                
                // Update term grade with 2 decimals for accuracy
                if (data.term_grade && data.term_grade.term_grade !== null) {
                    termGradeCell.textContent = parseFloat(data.term_grade.term_grade).toFixed(2);
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

// Exam Max Score Modal Functions
function openExamMaxScoreModal() {
    document.getElementById('examMaxScoreModal').classList.remove('hidden');
    // Get the first exam max score input value as default
    const firstMaxScore = document.querySelector('.exam-max-score-input')?.value || 100;
    document.getElementById('bulkExamMaxScore').value = firstMaxScore;
}

function closeExamMaxScoreModal() {
    document.getElementById('examMaxScoreModal').classList.add('hidden');
}

async function applyBulkExamMaxScore() {
    const newMaxScore = parseFloat(document.getElementById('bulkExamMaxScore').value);
    
    if (!newMaxScore || newMaxScore <= 0) {
        showNotification('Please enter a valid max score', 'error');
        return;
    }
    
    // Update all exam max score inputs and save each one
    const examScoreInputs = document.querySelectorAll('.exam-score-input');
    let updatedCount = 0;
    let errors = 0;
    
    // Show loading state
    showNotification('Updating exam max scores...', 'info');
    
    for (const scoreInput of examScoreInputs) {
        try {
            const studentMappingId = scoreInput.dataset.studentMappingId;
            const subjectId = scoreInput.dataset.subjectId;
            const term = scoreInput.dataset.term;
            const examScore = scoreInput.value || null;
            
            // Find the corresponding max score input and update it
            const maxScoreInput = scoreInput.closest('td').querySelector('.exam-max-score-input');
            if (maxScoreInput) {
                maxScoreInput.value = newMaxScore;
            }
            
            // Save to database
            const response = await fetch('{{ route("grades.update-exam-score") }}', {
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
                    exam_max_score: newMaxScore
                })
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                updatedCount++;
                
                // Update the exam grade and term grade in the UI
                const row = scoreInput.closest('tr');
                if (row && data.term_grade) {
                    // Update exam grade
                    const examGradeCell = row.querySelector('td.bg-orange-25 .text-sm');
                    if (examGradeCell && data.term_grade.exam_grade !== null) {
                        examGradeCell.textContent = parseFloat(data.term_grade.exam_grade).toFixed(2);
                    }
                    
                    // Update term grade
                    const termGradeCell = row.querySelector('td.bg-red-25 .text-sm');
                    if (termGradeCell && data.term_grade.term_grade !== null) {
                        termGradeCell.textContent = parseFloat(data.term_grade.term_grade).toFixed(2);
                    }
                }
            } else {
                errors++;
            }
        } catch (error) {
            console.error('Error updating max score:', error);
            errors++;
        }
    }
    
    closeExamMaxScoreModal();
    
    if (errors === 0) {
        showNotification(`Successfully updated exam max score to ${newMaxScore} for ${updatedCount} students`, 'success');
    } else {
        showNotification(`Updated ${updatedCount} students, ${errors} failed`, 'warning');
    }
}
</script>

<!-- Exam Max Score Modal -->
<div id="examMaxScoreModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Edit Exam Max Score</h3>
            <button onclick="closeExamMaxScoreModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Max Score (will apply to all students)
            </label>
            <input 
                type="number" 
                id="bulkExamMaxScore"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                min="1"
                step="0.01"
                placeholder="100"
            >
            <p class="text-xs text-gray-500 mt-2">
                This will update the exam max score for all students in this term.
            </p>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button 
                onclick="closeExamMaxScoreModal()"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                Cancel
            </button>
            <button 
                onclick="applyBulkExamMaxScore()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                Apply to All
            </button>
        </div>
    </div>
</div>

@endsection