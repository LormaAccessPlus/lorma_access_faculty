<div x-data="activityManager({{ $subject->id }})" class="space-y-6">
    <!-- Progress Overview Section -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Activity Progress Overview</h3>
            <p class="text-sm text-gray-600 mt-1">{{ $subject->subject_code }} - {{ $subject->subject_name }}</p>
        </div>
        
        <div class="p-6">
            <!-- Term Progress Bars -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <template x-for="term in ['prelim', 'midterm', 'finals']" :key="term">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-gray-900 capitalize" x-text="term"></h4>
                            <span class="text-sm font-medium text-gray-600" x-text="getTotalActivitiesForTerm(term) + ' activities'"></span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                            <div class="h-2 rounded-full transition-all duration-300"
                                 :class="getTotalActivitiesForTerm(term) > 0 ? 'bg-green-500' : 'bg-gray-300'"
                                 :style="'width: ' + Math.min((getTotalActivitiesForTerm(term) / 5) * 100, 100) + '%'"></div>
                        </div>
                        
                        <!-- Activity Breakdown -->
                        <div class="space-y-1 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>📚 Lecture:</span>
                                <span x-text="getActivitiesByTermAndType(term, 'lecture').length"></span>
                            </div>
                            <div x-show="'{{ $subject->type }}' === 'lecture_lab'" class="flex justify-between">
                                <span>🧪 Lab:</span>
                                <span x-text="getActivitiesByTermAndType(term, 'lab').length"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Overall Stats -->
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600" x-text="getTotalActivities()"></div>
                    <div class="text-sm text-blue-800">Total Activities</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600" x-text="getConnectedActivities()"></div>
                    <div class="text-sm text-green-800">Connected to GCR</div>
                </div>
                <div class="text-center p-3 bg-purple-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600" x-text="getLectureActivities()"></div>
                    <div class="text-sm text-purple-800">Lecture Activities</div>
                </div>
                <div x-show="'{{ $subject->type }}' === 'lecture_lab'" class="text-center p-3 bg-orange-50 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600" x-text="getLabActivities()"></div>
                    <div class="text-sm text-orange-800">Lab Activities</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Management Section -->
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900">Activity Management</h3>
                    <p class="text-sm text-gray-600 mt-1">Create, edit, and organize your activities</p>
                </div>
                <div class="flex items-center space-x-3">
                    @if($subject->gcr_class_id)
                    <button @click="showCourseworkModal = true; loadAvailableCoursework()" 
                            :disabled="loadingCoursework"
                            class="flex items-center px-4 py-2 text-white rounded-lg font-medium transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md"
                            style="background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(66, 133, 244, 0.3)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)';">
                        <i class="fab fa-google mr-2"></i>
                        <span x-show="!loadingCoursework">Import from Classroom</span>
                        <span x-show="loadingCoursework">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
                        </span>
                    </button>
                    @endif
                    <button @click="showAddActivityModal = true" 
                            class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md"
                            onmouseover="this.style.transform='translateY(-1px)';"
                            onmouseout="this.style.transform='translateY(0)';">
                        <i class="fas fa-plus mr-2"></i>Add New Activity
                    </button>
                </div>
            </div>
            
            @if(!$subject->gcr_class_id)
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fab fa-google text-blue-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-800">Google Classroom Integration</h4>
                        <p class="text-sm text-blue-700 mt-1">
                            Connect this subject to Google Classroom to automatically import activities and sync grades.
                        </p>
                        <div class="mt-2">
                            <a href="{{ route('classroom.index') }}" 
                               class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition-colors">
                                <i class="fas fa-link mr-1"></i>Connect to Google Classroom
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

    <!-- Activity Organization by Term -->
    <div class="p-6">
        <!-- Term Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <template x-for="term in ['prelim', 'midterm', 'finals']" :key="term">
                    <button @click="activeTab = term"
                            :class="activeTab === term ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm capitalize transition-colors">
                        <span x-text="term"></span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="activeTab === term ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                              x-text="getTotalActivitiesForTerm(term)"></span>
                    </button>
                </template>
            </nav>
        </div>

        <!-- Tab Content -->
        <template x-for="term in ['prelim', 'midterm', 'finals']" :key="term">
            <div x-show="activeTab === term" class="space-y-6">
                <!-- Lecture Activities -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-medium text-gray-900 flex items-center">
                            <i class="fas fa-chalkboard-teacher text-blue-500 mr-2"></i>
                            Lecture Activities
                        </h4>
                        <span class="text-sm text-gray-500" x-text="getActivitiesByTermAndType(term, 'lecture').length + ' activities'"></span>
                    </div>
                    
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <template x-for="activity in getActivitiesByTermAndType(term, 'lecture')" :key="activity.id">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200 hover:shadow-md transition-all duration-200">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-gray-900 mb-1" x-text="activity.name"></h5>
                                        <div class="flex items-center space-x-2">
                                            <span x-show="activity.gcr_assignment_id" 
                                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fab fa-google mr-1"></i>Connected
                                            </span>
                                            <span x-show="!activity.gcr_assignment_id" 
                                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                Manual
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <button @click="editActivity(activity)" 
                                                class="p-2 text-blue-600 hover:bg-blue-200 rounded-full transition-colors"
                                                title="Edit Activity">
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <button @click="deleteActivity(activity)" 
                                                class="p-2 text-red-600 hover:bg-red-200 rounded-full transition-colors"
                                                title="Delete Activity">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Max Score:</span>
                                        <span class="font-medium text-gray-900" x-text="activity.max_score"></span>
                                    </div>
                                    <div x-show="activity.weight" class="flex justify-between text-sm">
                                        <span class="text-gray-600">Weight:</span>
                                        <span class="font-medium text-gray-900" x-text="activity.weight + '%'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Empty State for Lecture Activities -->
                        <div x-show="getActivitiesByTermAndType(term, 'lecture').length === 0" 
                             class="col-span-full">
                            <div class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <i class="fas fa-chalkboard-teacher text-gray-400 text-3xl mb-3"></i>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">No Lecture Activities</h4>
                                <p class="text-gray-600 mb-4">No lecture activities found for <span class="capitalize font-medium" x-text="term"></span> term.</p>
                                <button @click="showAddActivityModal = true; newActivity.term = term; newActivity.type = 'lecture'" 
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Add Lecture Activity
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lab Activities (only if subject has lab) -->
                <div x-show="'{{ $subject->type }}' === 'lecture_lab'">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-medium text-gray-900 flex items-center">
                            <i class="fas fa-flask text-purple-500 mr-2"></i>
                            Laboratory Activities
                        </h4>
                        <span class="text-sm text-gray-500" x-text="getActivitiesByTermAndType(term, 'lab').length + ' activities'"></span>
                    </div>
                    
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <template x-for="activity in getActivitiesByTermAndType(term, 'lab')" :key="activity.id">
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200 hover:shadow-md transition-all duration-200">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-gray-900 mb-1" x-text="activity.name"></h5>
                                        <div class="flex items-center space-x-2">
                                            <span x-show="activity.gcr_assignment_id" 
                                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fab fa-google mr-1"></i>Connected
                                            </span>
                                            <span x-show="!activity.gcr_assignment_id" 
                                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                Manual
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <button @click="editActivity(activity)" 
                                                class="p-2 text-purple-600 hover:bg-purple-200 rounded-full transition-colors"
                                                title="Edit Activity">
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <button @click="deleteActivity(activity)" 
                                                class="p-2 text-red-600 hover:bg-red-200 rounded-full transition-colors"
                                                title="Delete Activity">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Max Score:</span>
                                        <span class="font-medium text-gray-900" x-text="activity.max_score"></span>
                                    </div>
                                    <div x-show="activity.weight" class="flex justify-between text-sm">
                                        <span class="text-gray-600">Weight:</span>
                                        <span class="font-medium text-gray-900" x-text="activity.weight + '%'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Empty State for Lab Activities -->
                        <div x-show="getActivitiesByTermAndType(term, 'lab').length === 0" 
                             class="col-span-full">
                            <div class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <i class="fas fa-flask text-gray-400 text-3xl mb-3"></i>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">No Laboratory Activities</h4>
                                <p class="text-gray-600 mb-4">No laboratory activities found for <span class="capitalize font-medium" x-text="term"></span> term.</p>
                                <button @click="showAddActivityModal = true; newActivity.term = term; newActivity.type = 'lab'" 
                                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Add Lab Activity
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Add Activity Modal -->
    <div x-show="showAddActivityModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
         style="display: none;"
         @click.self="showAddActivityModal = false; resetNewActivity()">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900">Add New Activity</h3>
                    <button @click="showAddActivityModal = false; resetNewActivity()" 
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <form @submit.prevent="addActivity()" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag mr-1"></i>Activity Name
                        </label>
                        <input type="text" 
                               x-model="newActivity.name" 
                               required
                               placeholder="Enter activity name..."
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-layer-group mr-1"></i>Type
                            </label>
                            <select x-model="newActivity.type" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Select Type</option>
                                <option value="lecture">📚 Lecture</option>
                                @if($subject->type === 'lecture_lab')
                                <option value="lab">🧪 Laboratory</option>
                                @endif
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt mr-1"></i>Term
                            </label>
                            <select x-model="newActivity.term" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Select Term</option>
                                <option value="prelim">📝 Prelims</option>
                                <option value="midterm">📊 Midterm</option>
                                <option value="finals">🎯 Finals</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-star mr-1"></i>Max Score
                            </label>
                            <input type="number" 
                                   x-model="newActivity.max_score" 
                                   required
                                   min="0" 
                                   step="0.01"
                                   placeholder="100"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-percentage mr-1"></i>Weight (%)
                            </label>
                            <input type="number" 
                                   x-model="newActivity.weight" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   placeholder="Optional"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                        <button type="button" 
                                @click="showAddActivityModal = false; resetNewActivity()"
                                class="px-6 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                :disabled="submitting"
                                class="flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium transition-all">
                            <span x-show="!submitting">
                                <i class="fas fa-plus mr-2"></i>Add Activity
                            </span>
                            <span x-show="submitting">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Adding...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Google Classroom Coursework Selection Modal -->
    <div x-show="showCourseworkModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
         style="display: none;"
         @click.self="showCourseworkModal = false; resetCourseworkModal()">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-green-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                            <i class="fab fa-google mr-3 text-blue-500"></i>
                            Import from Google Classroom
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">Select coursework to import as activities</p>
                    </div>
                    <button @click="showCourseworkModal = false; resetCourseworkModal()" 
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Stats Bar -->
                <div x-show="availableCoursework.length > 0" class="mt-4 grid grid-cols-3 gap-4">
                    <div class="text-center p-2 bg-white rounded-lg">
                        <div class="text-lg font-bold text-blue-600" x-text="courseworkStats.total_count"></div>
                        <div class="text-xs text-blue-800">Total Coursework</div>
                    </div>
                    <div class="text-center p-2 bg-white rounded-lg">
                        <div class="text-lg font-bold text-green-600" x-text="courseworkStats.imported_count"></div>
                        <div class="text-xs text-green-800">Already Imported</div>
                    </div>
                    <div class="text-center p-2 bg-white rounded-lg">
                        <div class="text-lg font-bold text-orange-600" x-text="courseworkStats.available_count"></div>
                        <div class="text-xs text-orange-800">Available to Import</div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="flex-1 overflow-hidden">
                <!-- Loading State -->
                <div x-show="loadingCoursework" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-4"></i>
                        <p class="text-gray-600">Loading coursework from Google Classroom...</p>
                    </div>
                </div>
                
                <!-- Import Options -->
                <div x-show="!loadingCoursework && availableCoursework.length > 0" class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Term</label>
                            <select x-model="importSettings.default_term" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="prelim">📝 Prelims</option>
                                <option value="midterm">📊 Midterm</option>
                                <option value="finals">🎯 Finals</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Type</label>
                            <select x-model="importSettings.default_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="lecture">📚 Lecture</option>
                                @if($subject->type === 'lecture_lab')
                                <option value="lab">🧪 Laboratory</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <button @click="selectAllAvailable()" 
                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Select All Available
                            </button>
                            <button @click="deselectAll()" 
                                    class="text-sm text-gray-600 hover:text-gray-800 font-medium">
                                Deselect All
                            </button>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span x-text="selectedCoursework.length"></span> selected
                        </div>
                    </div>
                </div>
                
                <!-- Coursework List -->
                <div x-show="!loadingCoursework" class="max-h-96 overflow-y-auto">
                    <div x-show="availableCoursework.length === 0" class="text-center py-12">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">No Coursework Found</h4>
                        <p class="text-gray-600">No coursework found in your Google Classroom.</p>
                    </div>
                    
                    <div x-show="availableCoursework.length > 0" class="divide-y divide-gray-200">
                        <template x-for="coursework in availableCoursework" :key="coursework.id">
                            <div class="p-4 hover:bg-gray-50 transition-colors"
                                 :class="coursework.is_imported ? 'opacity-60' : ''">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <input type="checkbox" 
                                               :value="coursework.id"
                                               x-model="selectedCoursework"
                                               :disabled="coursework.is_imported"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-sm font-medium text-gray-900" x-text="coursework.title"></h4>
                                            <span x-show="coursework.is_imported" 
                                                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ✓ Imported
                                            </span>
                                        </div>
                                        <p x-show="coursework.description" 
                                           class="text-sm text-gray-600 mt-1 line-clamp-2" 
                                           x-text="coursework.description"></p>
                                        <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                            <span>Max Points: <span class="font-medium" x-text="coursework.max_points"></span></span>
                                            <span x-show="coursework.due_date">Due: <span x-text="formatDate(coursework.due_date)"></span></span>
                                            <span class="capitalize" x-text="coursework.state"></span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a :href="coursework.alternate_link" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div x-show="!loadingCoursework && availableCoursework.length > 0" 
                 class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span x-text="selectedCoursework.length"></span> coursework selected for import
                    </div>
                    <div class="flex items-center space-x-3">
                        <button @click="showCourseworkModal = false; resetCourseworkModal()"
                                class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                            Cancel
                        </button>
                        <button @click="importSelectedCoursework()" 
                                :disabled="selectedCoursework.length === 0 || importing"
                                class="flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium transition-all">
                            <span x-show="!importing">
                                <i class="fab fa-google mr-2"></i>Import Selected
                            </span>
                            <span x-show="importing">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Importing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom scrollbar for better UX */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Smooth transitions for cards */
.activity-card {
    transition: all 0.2s ease-in-out;
}

.activity-card:hover {
    transform: translateY(-2px);
}

/* Loading animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Custom focus styles */
input:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button hover effects */
.btn-hover {
    transition: all 0.2s ease-in-out;
}

.btn-hover:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Line clamp for text truncation */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Coursework modal styles */
.coursework-item {
    transition: all 0.2s ease-in-out;
}

.coursework-item:hover {
    background-color: #f9fafb;
}

/* Custom checkbox styles */
input[type="checkbox"]:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

/* Modal backdrop blur */
.modal-backdrop {
    backdrop-filter: blur(4px);
}
</style>

<script>
function activityManager(subjectId) {
    return {
        activities: [],
        showAddActivityModal: false,
        showCourseworkModal: false,
        syncing: false,
        submitting: false,
        loadingCoursework: false,
        importing: false,
        activeTab: 'prelim',
        availableCoursework: [],
        selectedCoursework: [],
        courseworkStats: {
            total_count: 0,
            imported_count: 0,
            available_count: 0
        },
        importSettings: {
            default_term: 'prelim',
            default_type: 'lecture'
        },
        newActivity: {
            name: '',
            type: '',
            term: '',
            max_score: '',
            weight: '',
            subject_id: subjectId
        },
        
        init() {
            this.loadActivities();
        },
        
        async loadActivities() {
            try {
                const response = await fetch(`/api/subjects/${subjectId}/activities/organized`);
                const data = await response.json();
                this.activities = data.activities;
            } catch (error) {
                console.error('Error loading activities:', error);
            }
        },
        
        getActivitiesByTermAndType(term, type) {
            return this.activities[term] && this.activities[term][type] ? this.activities[term][type] : [];
        },
        
        getTotalActivitiesForTerm(term) {
            const lecture = this.getActivitiesByTermAndType(term, 'lecture').length;
            const lab = this.getActivitiesByTermAndType(term, 'lab').length;
            return lecture + lab;
        },
        
        getTotalActivities() {
            let total = 0;
            ['prelim', 'midterm', 'finals'].forEach(term => {
                total += this.getTotalActivitiesForTerm(term);
            });
            return total;
        },
        
        getConnectedActivities() {
            let connected = 0;
            ['prelim', 'midterm', 'finals'].forEach(term => {
                ['lecture', 'lab'].forEach(type => {
                    const activities = this.getActivitiesByTermAndType(term, type);
                    connected += activities.filter(activity => activity.gcr_assignment_id).length;
                });
            });
            return connected;
        },
        
        getLectureActivities() {
            let total = 0;
            ['prelim', 'midterm', 'finals'].forEach(term => {
                total += this.getActivitiesByTermAndType(term, 'lecture').length;
            });
            return total;
        },
        
        getLabActivities() {
            let total = 0;
            ['prelim', 'midterm', 'finals'].forEach(term => {
                total += this.getActivitiesByTermAndType(term, 'lab').length;
            });
            return total;
        },
        
        async addActivity() {
            this.submitting = true;
            try {
                const response = await fetch('/activities', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.newActivity)
                });
                
                if (response.ok) {
                    await this.loadActivities();
                    this.showAddActivityModal = false;
                    this.resetNewActivity();
                    this.showNotification('Activity added successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showNotification('Error: ' + (error.message || 'Failed to add activity'), 'error');
                }
            } catch (error) {
                this.showNotification('Error: ' + error.message, 'error');
            } finally {
                this.submitting = false;
            }
        },
        
        editActivity(activity) {
            window.location.href = `/activities/${activity.id}/edit`;
        },
        
        async deleteActivity(activity) {
            if (!confirm(`Are you sure you want to delete "${activity.name}"?`)) {
                return;
            }
            
            try {
                const response = await fetch(`/activities/${activity.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    await this.loadActivities();
                    this.showNotification('Activity deleted successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showNotification('Error: ' + (error.message || 'Failed to delete activity'), 'error');
                }
            } catch (error) {
                this.showNotification('Error: ' + error.message, 'error');
            }
        },
        
        async loadAvailableCoursework() {
            this.loadingCoursework = true;
            try {
                const response = await fetch(`/classroom/${subjectId}/available-coursework`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.availableCoursework = data.coursework;
                    this.courseworkStats = {
                        total_count: data.total_count,
                        imported_count: data.imported_count,
                        available_count: data.available_count
                    };
                } else {
                    this.showNotification('Error: ' + (data.message || 'Failed to load coursework'), 'error');
                }
            } catch (error) {
                console.error('Load coursework error:', error);
                this.showNotification('Error: Failed to load coursework from Google Classroom.', 'error');
            } finally {
                this.loadingCoursework = false;
            }
        },

        async importSelectedCoursework() {
            if (this.selectedCoursework.length === 0) {
                this.showNotification('Please select at least one coursework to import.', 'warning');
                return;
            }

            this.importing = true;
            try {
                const response = await fetch(`/classroom/${subjectId}/import-selected`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        coursework_ids: this.selectedCoursework,
                        default_term: this.importSettings.default_term,
                        default_type: this.importSettings.default_type
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    await this.loadActivities();
                    this.showCourseworkModal = false;
                    this.resetCourseworkModal();
                    
                    let successMessage = data.message;
                    if (data.imported_count > 0) {
                        successMessage += ` (${data.imported_count} activities imported)`;
                    }
                    this.showNotification(successMessage, 'success');
                    
                    if (data.errors && data.errors.length > 0) {
                        setTimeout(() => {
                            this.showNotification('Some warnings occurred. Check console for details.', 'warning');
                            console.warn('Import warnings:', data.errors);
                        }, 2000);
                    }
                } else {
                    this.showNotification('Error: ' + (data.message || 'Failed to import coursework'), 'error');
                }
            } catch (error) {
                console.error('Import error:', error);
                this.showNotification('Error: Failed to import coursework.', 'error');
            } finally {
                this.importing = false;
            }
        },

        selectAllAvailable() {
            this.selectedCoursework = this.availableCoursework
                .filter(coursework => !coursework.is_imported)
                .map(coursework => coursework.id);
        },

        deselectAll() {
            this.selectedCoursework = [];
        },

        resetCourseworkModal() {
            this.availableCoursework = [];
            this.selectedCoursework = [];
            this.courseworkStats = {
                total_count: 0,
                imported_count: 0,
                available_count: 0
            };
            this.importSettings = {
                default_term: 'prelim',
                default_type: 'lecture'
            };
        },

        formatDate(dateString) {
            if (!dateString) return '';
            try {
                return new Date(dateString).toLocaleDateString();
            } catch (e) {
                return dateString;
            }
        },
        
        resetNewActivity() {
            this.newActivity = {
                name: '',
                type: '',
                term: '',
                max_score: '',
                weight: '',
                subject_id: subjectId
            };
        },
        
        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                type === 'warning' ? 'bg-yellow-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove after 4 seconds
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }
    }
}
</script>