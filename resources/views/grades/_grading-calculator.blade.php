<!-- Grading Formula Configuration Section -->
<div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-teal-50">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="fas fa-calculator mr-2" style="color: #08695A;"></i>
            Grading Formula Configuration
        </h3>
        <p class="text-sm text-gray-600 mt-1">
            Define how grades are calculated | Actual grades are computed in the grade table below
        </p>
    </div>

    <div x-data="{
        expanded: false
    }">
        <!-- Formula Summary Card -->
        <button @click="expanded = !expanded" 
                class="w-full flex items-center justify-between px-6 py-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all">
            <div class="flex items-center space-x-6">
                <i class="fas fa-formula text-3xl text-blue-600"></i>
                <div class="text-left">
                    <div class="text-sm text-gray-600 font-medium">{{ ucfirst($term) }} Term Formula</div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ $gradingConfig->class_standing_weight ?? 40 }}% Activities + {{ $gradingConfig->exam_weight ?? 60 }}% Exam
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500">Click to edit formula</span>
                <i class="fas fa-chevron-down transition-transform text-gray-400" :class="{ 'rotate-180': expanded }"></i>
            </div>
        </button>

        <!-- Formula Configuration -->
        <div x-show="expanded" x-cloak x-transition class="mt-4 bg-white rounded-lg shadow-lg border border-gray-200 p-6">
            
            <!-- Term Grade Formula (Editable per Term) -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border-2 border-blue-300" x-data="{
                activitiesWeight: {{ $gradingConfig->class_standing_weight ?? 40 }},
                examWeight: {{ $gradingConfig->exam_weight ?? 60 }},
                get termTotal() {
                    return parseFloat(this.activitiesWeight) + parseFloat(this.examWeight);
                },
                saveTermFormula() {
                    const config = {
                        term: '{{ $term }}',
                        class_standing_weight: parseFloat(this.activitiesWeight),
                        exam_weight: parseFloat(this.examWeight)
                    };
                    
                    if (config.class_standing_weight + config.exam_weight !== 100) {
                        alert('Weights must total 100%');
                        return;
                    }
                    
                    fetch('{{ route('grades.save-grading-config', $subject) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(config)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('{{ ucfirst($term) }} term formula saved successfully! Grades will be recalculated.');
                            window.location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to save formula'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to save formula');
                    });
                }
            }">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-book mr-2 text-blue-600"></i>
                    {{ ucfirst($term) }} Term Grade Formula (Editable)
                </h4>
                
                <form @submit.prevent="saveTermFormula()" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-tasks mr-1 text-teal-600"></i>
                                All Activities Weight (%)
                            </label>
                            <input type="number" x-model="activitiesWeight" min="0" max="100" step="0.01"
                                   class="w-full px-3 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-center text-lg font-semibold">
                            <p class="text-xs text-gray-500 mt-1">Lecture + Lab activities</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-file-alt mr-1 text-purple-600"></i>
                                All Exams Weight (%)
                            </label>
                            <input type="number" x-model="examWeight" min="0" max="100" step="0.01"
                                   class="w-full px-3 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-center text-lg font-semibold">
                            <p class="text-xs text-gray-500 mt-1">Exam scores</p>
                        </div>
                        <div class="flex items-end">
                            <div class="w-full text-center p-3 rounded-lg" :class="termTotal === 100 ? 'bg-green-100 border-2 border-green-500' : 'bg-red-100 border-2 border-red-500'">
                                <div class="text-xs text-gray-600 mb-1">Total</div>
                                <div class="text-2xl font-bold" :class="termTotal === 100 ? 'text-green-700' : 'text-red-700'" x-text="termTotal + '%'"></div>
                                <div class="text-xs mt-1" :class="termTotal === 100 ? 'text-green-600' : 'text-red-600'">
                                    <i :class="termTotal === 100 ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-4 border border-blue-200">
                        <div class="text-center mb-3">
                            <div class="text-sm text-gray-600 mb-1">{{ ucfirst($term) }} Term Formula</div>
                            <div class="text-lg font-bold text-blue-700">
                                Term Grade = (Activities × <span x-text="activitiesWeight"></span>%) + (Exam × <span x-text="examWeight"></span>%)
                            </div>
                        </div>
                        <div class="text-xs text-gray-600 text-center">
                            <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                            Example: If Activities=85%, Exam=90% → Grade = (85×<span x-text="activitiesWeight"></span>% + 90×<span x-text="examWeight"></span>%)
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-blue-200">
                        <p class="text-xs text-gray-600" x-show="termTotal !== 100">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-1"></i>
                            <span class="text-red-600 font-semibold">Warning:</span> Weights must total 100%
                        </p>
                        <button type="submit" 
                                :disabled="termTotal !== 100"
                                :class="termTotal === 100 ? 'opacity-100 cursor-pointer' : 'opacity-50 cursor-not-allowed'"
                                class="px-6 py-2 text-white rounded-lg font-medium transition-colors"
                                style="background-color: #08695A;"
                                onmouseover="if(this.disabled === false) this.style.backgroundColor='#065A4A';"
                                onmouseout="this.style.backgroundColor='#08695A';">
                            <i class="fas fa-save mr-2"></i>Save {{ ucfirst($term) }} Formula
                        </button>
                    </div>
                </form>
                
                <p class="text-xs text-gray-600 mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Note:</strong> Activities include all lecture and lab scores. The system will calculate the average of all activity scores for this term.
                </p>
            </div>



            <!-- Important Note -->
            <div class="mt-6 p-4 bg-yellow-50 rounded-lg border-2 border-yellow-300">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-yellow-600 text-xl mr-3 mt-1"></i>
                    <div>
                        <h5 class="font-semibold text-gray-900 mb-2">How Grades Are Calculated</h5>
                        <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                            <li>Enter activity scores in the <strong>grade table below</strong> for each student</li>
                            <li>System calculates <strong>Class Standing</strong> from activity scores</li>
                            <li>Enter <strong>Exam scores</strong> in the grade table</li>
                            <li>System computes <strong>Term Grade</strong> = (CS × 40%) + (Exam × 60%)</li>
                            <li>System calculates <strong>Final Rating</strong> using the weights configured above</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


