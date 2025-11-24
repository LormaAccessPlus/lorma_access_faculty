<!-- Period Calculator Component -->
@php
    $periodPrefix = $period; // prelim, midterm, finals
    $colorClass = $period === 'prelim' ? 'blue' : ($period === 'midterm' ? 'green' : 'purple');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Left: Input Components -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Lecture Components -->
        <div class="bg-{{ $colorClass }}-50 rounded-lg p-4 border border-{{ $colorClass }}-200">
            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-book mr-2 text-{{ $colorClass }}-600"></i>
                Lecture Components
            </h5>
            <div class="space-y-3">
                <!-- Lecture A1 -->
                <div class="bg-white rounded p-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lecture A1</label>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" x-model="{{ $periodPrefix }}LectureA1Score" placeholder="Score" min="0" step="0.01"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $colorClass }}-500 text-center">
                        <input type="number" x-model="{{ $periodPrefix }}LectureA1Total" placeholder="Total" min="0" step="0.01"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $colorClass }}-500 text-center">
                        <div class="flex items-center justify-center bg-{{ $colorClass }}-100 rounded-lg">
                            <span class="text-lg font-bold text-{{ $colorClass }}-700" x-text="{{ $periodPrefix }}LectureA1Percent + '%'"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <span class="text-xs text-gray-500 text-center">Score</span>
                        <span class="text-xs text-gray-500 text-center">Total</span>
                        <span class="text-xs text-gray-500 text-center">Percentage</span>
                    </div>
                </div>

                <!-- Lecture A2 -->
                <div class="bg-white rounded p-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lecture A2</label>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" x-model="{{ $periodPrefix }}LectureA2Score" placeholder="Score" min="0" step="0.01"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $colorClass }}-500 text-center">
                        <input type="number" x-model="{{ $periodPrefix }}LectureA2Total" placeholder="Total" min="0" step="0.01"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $colorClass }}-500 text-center">
                        <div class="flex items-center justify-center bg-{{ $colorClass }}-100 rounded-lg">
                            <span class="text-lg font-bold text-{{ $colorClass }}-700" x-text="{{ $periodPrefix }}LectureA2Percent + '%'"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <span class="text-xs text-gray-500 text-center">Score</span>
                        <span class="text-xs text-gray-500 text-center">Total</span>
                        <span class="text-xs text-gray-500 text-center">Percentage</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lab Component -->
        <div class="bg-pink-50 rounded-lg p-4 border border-pink-200">
            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-flask mr-2 text-pink-600"></i>
                Laboratory
            </h5>
            <div class="bg-white rounded p-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Lab CS (Class Standing)</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" x-model="{{ $periodPrefix }}LabCS" placeholder="Score (0-100)" min="0" max="100" step="0.01"
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 text-center">
                    <div class="flex items-center justify-center bg-pink-100 rounded-lg">
                        <span class="text-lg font-bold text-pink-700" x-text="{{ $periodPrefix }}LabCS + '%'"></span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-1">
                    <span class="text-xs text-gray-500 text-center">Score / 100</span>
                    <span class="text-xs text-gray-500 text-center">Percentage</span>
                </div>
            </div>
        </div>

        <!-- Exam & Task -->
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                Exam & Task
            </h5>
            <div class="space-y-3">
                <!-- Exam -->
                <div class="bg-white rounded p-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Exam Score</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" x-model="{{ $periodPrefix }}Exam" placeholder="Score (0-100)" min="0" max="100" step="0.01"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-center">
                        <div class="flex items-center justify-center bg-purple-100 rounded-lg">
                            <span class="text-lg font-bold text-purple-700" x-text="{{ $periodPrefix }}Exam + '%'"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <span class="text-xs text-gray-500 text-center">Score / 100</span>
                        <span class="text-xs text-gray-500 text-center">Percentage</span>
                    </div>
                </div>

                <!-- Task/TS -->
                <div class="bg-white rounded p-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">TS (Task Score)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" x-model="{{ $periodPrefix }}TS" placeholder="Score (0-100)" min="0" max="100" step="0.01"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-center">
                        <div class="flex items-center justify-center bg-purple-100 rounded-lg">
                            <span class="text-lg font-bold text-purple-700" x-text="{{ $periodPrefix }}TS + '%'"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <span class="text-xs text-gray-500 text-center">Score / 100</span>
                        <span class="text-xs text-gray-500 text-center">Percentage</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Calculations -->
    <div class="space-y-4">
        <!-- Class Standing -->
        <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg p-4 border-2 border-teal-300">
            <h6 class="font-semibold text-gray-900 mb-3 flex items-center text-sm">
                <i class="fas fa-chart-bar mr-2 text-teal-600"></i>
                Class Standing (40%)
            </h6>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="text-gray-600">Lecture A1:</span>
                    <span class="font-semibold" x-text="{{ $periodPrefix }}LectureA1Percent + '%'"></span>
                </div>
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="text-gray-600">Lecture A2:</span>
                    <span class="font-semibold" x-text="{{ $periodPrefix }}LectureA2Percent + '%'"></span>
                </div>
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="text-gray-600">Lab CS:</span>
                    <span class="font-semibold" x-text="{{ $periodPrefix }}LabCS + '%'"></span>
                </div>
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="text-gray-600">Task/TS:</span>
                    <span class="font-semibold" x-text="{{ $periodPrefix }}TS + '%'"></span>
                </div>
                <div class="border-t-2 border-teal-400 pt-2 mt-2">
                    <div class="flex justify-between items-center p-2 bg-white rounded">
                        <span class="font-bold text-gray-900">Average CS:</span>
                        <span class="text-xl font-bold text-teal-600" x-text="{{ $periodPrefix }}CS + '%'"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border-2 border-purple-300">
            <h6 class="font-semibold text-gray-900 mb-3 flex items-center text-sm">
                <i class="fas fa-graduation-cap mr-2 text-purple-600"></i>
                Exam (60%)
            </h6>
            <div class="text-center p-2 bg-white rounded">
                <div class="text-3xl font-bold text-purple-600" x-text="{{ $periodPrefix }}Exam + '%'"></div>
            </div>
        </div>

        <!-- Period Grade -->
        <div class="bg-gradient-to-br from-{{ $colorClass }}-50 to-{{ $colorClass }}-100 rounded-lg p-4 border-2 border-{{ $colorClass }}-400">
            <h6 class="font-semibold text-gray-900 mb-3 flex items-center text-sm">
                <i class="fas fa-trophy mr-2 text-{{ $colorClass }}-600"></i>
                {{ ucfirst($period) }} Grade
            </h6>
            <div class="space-y-2 text-xs mb-3">
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="text-gray-600">CS × 40%:</span>
                    <span class="font-semibold" x-text="({{ $periodPrefix }}CS * 0.40).toFixed(2)"></span>
                </div>
                <div class="flex justify-between p-2 bg-white rounded">
                    <span class="text-gray-600">Exam × 60%:</span>
                    <span class="font-semibold" x-text="({{ $periodPrefix }}Exam * 0.60).toFixed(2)"></span>
                </div>
            </div>
            <div class="border-t-2 border-{{ $colorClass }}-400 pt-3">
                <div class="text-center p-3 bg-white rounded">
                    <div class="text-xs text-gray-600 mb-1">Final Grade</div>
                    <div class="text-4xl font-bold text-{{ $colorClass }}-600" x-text="{{ $periodPrefix }}Grade + '%'"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formula Reference -->
<div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
    <p class="text-xs text-gray-600">
        <strong>Formula:</strong> Grade = (CS × 40%) + (Exam × 60%) where CS = Average(Lecture A1, Lecture A2, Lab CS, Task/TS)
    </p>
</div>
