@extends('layouts.admin')

@section('title', 'Grading - ' . $subject->subject_name)

@section('page-title', $subject->subject_name)

@section('breadcrumbs')
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <a href="{{ route('grading.index') }}" class="text-gray-500 hover:text-gray-700">Grading</a>
    </li>
    <li class="flex items-center">
        <i class="fas fa-chevron-right text-xs mx-2"></i>
        <span class="text-gray-900">{{ $subject->subject_code }}</span>
    </li>
@endsection

@section('content')
<!-- Subject Header -->
<div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold mb-2">{{ $subject->subject_name }}</h2>
            <div class="flex items-center gap-4 text-blue-100">
                <span>
                    <i class="fas fa-tag mr-1"></i>{{ $subject->subject_code }} - {{ $subject->section }}
                </span>
                <span>
                    <i class="fas fa-users mr-1"></i>{{ $subject->studentMappings->count() }} Students
                </span>
            </div>
        </div>
        <div>
            <a href="{{ route('grading.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Grading
            </a>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="bg-white rounded-lg shadow-sm mb-6" x-data="{ activeTab: 'prelim' }">
    <!-- Tab Headers -->
    <div class="border-b border-gray-200">
        <nav class="flex -mb-px">
            <button @click="activeTab = 'prelim'" 
                    :class="activeTab === 'prelim' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-calendar-alt mr-2"></i>
                Prelim
                @if($prelim && $prelim->components->count() > 0)
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i>Configured
                    </span>
                @else
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Not Configured
                    </span>
                @endif
            </button>
            
            <button @click="activeTab = 'midterm'" 
                    :class="activeTab === 'midterm' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-calendar-alt mr-2"></i>
                Midterm
                @if($midterm && $midterm->components->count() > 0)
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i>Configured
                    </span>
                @else
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Not Configured
                    </span>
                @endif
            </button>
            
            <button @click="activeTab = 'finals'" 
                    :class="activeTab === 'finals' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-calendar-alt mr-2"></i>
                Finals
                @if($finals && $finals->components->count() > 0)
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i>Configured
                    </span>
                @else
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Not Configured
                    </span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
        <!-- Prelim Tab -->
        <div x-show="activeTab === 'prelim'" x-transition>
            @if($prelim)
                <div class="space-y-6">
                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Components</span>
                                <span class="text-2xl font-bold text-blue-600">{{ $prelim->components->count() }}</span>
                            </div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Items</span>
                                <span class="text-2xl font-bold text-purple-600">{{ $prelim->components->sum(fn($c) => $c->items->count()) }}</span>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Status</span>
                                @if($prelim->components->count() > 0)
                                    <span class="text-sm font-semibold text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i>Ready
                                    </span>
                                @else
                                    <span class="text-sm font-semibold text-yellow-600">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Setup Needed
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Components List -->
                    @if($prelim->components->count() > 0)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Grading Components</h3>
                            <div class="space-y-3">
                                @foreach($prelim->components as $component)
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900">{{ $component->component_name }}</h4>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 mr-2">
                                                        {{ ucfirst($component->component_type) }}
                                                    </span>
                                                    <span class="text-gray-500">Weight: {{ $component->weight_percentage }}%</span>
                                                    <span class="mx-2">•</span>
                                                    <span class="text-gray-500">{{ $component->items->count() }} items</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Components Configured</h3>
                            <p class="text-gray-600 mb-6">Configure grading components to start using this term.</p>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('grading.configure', $prelim->id) }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                            <i class="fas fa-cog mr-2"></i>
                            Configure Components
                        </a>
                        @if($prelim->components->count() > 0)
                            <a href="{{ route('grading.grade-sheet', $prelim->id) }}" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-table mr-2"></i>
                                Open Grade Sheet
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-circle text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Prelim Term Not Found</h3>
                    <p class="text-gray-600">This term hasn't been created yet.</p>
                </div>
            @endif
        </div>

        <!-- Midterm Tab -->
        <div x-show="activeTab === 'midterm'" x-transition>
            @if($midterm)
                <div class="space-y-6">
                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Components</span>
                                <span class="text-2xl font-bold text-purple-600">{{ $midterm->components->count() }}</span>
                            </div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Items</span>
                                <span class="text-2xl font-bold text-blue-600">{{ $midterm->components->sum(fn($c) => $c->items->count()) }}</span>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Status</span>
                                @if($midterm->components->count() > 0)
                                    <span class="text-sm font-semibold text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i>Ready
                                    </span>
                                @else
                                    <span class="text-sm font-semibold text-yellow-600">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Setup Needed
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Components List -->
                    @if($midterm->components->count() > 0)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Grading Components</h3>
                            <div class="space-y-3">
                                @foreach($midterm->components as $component)
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900">{{ $component->component_name }}</h4>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-800 mr-2">
                                                        {{ ucfirst($component->component_type) }}
                                                    </span>
                                                    <span class="text-gray-500">Weight: {{ $component->weight_percentage }}%</span>
                                                    <span class="mx-2">•</span>
                                                    <span class="text-gray-500">{{ $component->items->count() }} items</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Components Configured</h3>
                            <p class="text-gray-600 mb-6">Configure grading components to start using this term.</p>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('grading.configure', $midterm->id) }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                            <i class="fas fa-cog mr-2"></i>
                            Configure Components
                        </a>
                        @if($midterm->components->count() > 0)
                            <a href="{{ route('grading.grade-sheet', $midterm->id) }}" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-table mr-2"></i>
                                Open Grade Sheet
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-circle text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Midterm Term Not Found</h3>
                    <p class="text-gray-600">This term hasn't been created yet.</p>
                </div>
            @endif
        </div>

        <!-- Finals Tab -->
        <div x-show="activeTab === 'finals'" x-transition>
            @if($finals)
                <div class="space-y-6">
                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Components</span>
                                <span class="text-2xl font-bold text-green-600">{{ $finals->components->count() }}</span>
                            </div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Items</span>
                                <span class="text-2xl font-bold text-blue-600">{{ $finals->components->sum(fn($c) => $c->items->count()) }}</span>
                            </div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Status</span>
                                @if($finals->components->count() > 0)
                                    <span class="text-sm font-semibold text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i>Ready
                                    </span>
                                @else
                                    <span class="text-sm font-semibold text-yellow-600">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Setup Needed
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Components List -->
                    @if($finals->components->count() > 0)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Grading Components</h3>
                            <div class="space-y-3">
                                @foreach($finals->components as $component)
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900">{{ $component->component_name }}</h4>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 mr-2">
                                                        {{ ucfirst($component->component_type) }}
                                                    </span>
                                                    <span class="text-gray-500">Weight: {{ $component->weight_percentage }}%</span>
                                                    <span class="mx-2">•</span>
                                                    <span class="text-gray-500">{{ $component->items->count() }} items</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Components Configured</h3>
                            <p class="text-gray-600 mb-6">Configure grading components to start using this term.</p>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('grading.configure', $finals->id) }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                            <i class="fas fa-cog mr-2"></i>
                            Configure Components
                        </a>
                        @if($finals->components->count() > 0)
                            <a href="{{ route('grading.grade-sheet', $finals->id) }}" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-table mr-2"></i>
                                Open Grade Sheet
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-circle text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Finals Term Not Found</h3>
                    <p class="text-gray-600">This term hasn't been created yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
