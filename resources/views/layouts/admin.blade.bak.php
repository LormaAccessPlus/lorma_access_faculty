<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Faculty Grading System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: false }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0" 
         :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
        
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 px-4 bg-blue-600 text-white">
            <i class="fas fa-graduation-cap text-2xl mr-3"></i>
            <h1 class="text-lg font-bold">Faculty Portal</h1>
        </div>
        
        <!-- Navigation -->
        <nav class="mt-8">
            <div class="px-4 space-y-2">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                
                <!-- Subjects -->
                <div class="space-y-1">
                    <a href="{{ route('subjects.index') }}" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors {{ request()->routeIs('subjects.*') ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : '' }}">
                        <i class="fas fa-book w-5 h-5 mr-3"></i>
                        <span class="font-medium">Subjects</span>
                    </a>
                    @if(request()->routeIs('subjects.*'))
                    <div class="ml-8 space-y-1">
                        <a href="{{ route('subjects.create') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-plus w-4 h-4 mr-2"></i>
                            Add Subject
                        </a>
                    </div>
                    @endif
                </div>
                
                <!-- Activities -->
                <a href="{{ route('activities.index') }}" 
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors {{ request()->routeIs('activities.*') ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : '' }}">
                    <i class="fas fa-tasks w-5 h-5 mr-3"></i>
                    <span class="font-medium">Activities</span>
                </a>
                
                <!-- Google Classroom -->
                <div class="space-y-1">
                    <a href="{{ route('classroom.index') }}" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors {{ request()->routeIs('classroom.*') ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : '' }}">
                        <i class="fab fa-google-drive w-5 h-5 mr-3"></i>
                        <span class="font-medium">Google Classroom</span>
                    </a>
                </div>
                
                <!-- Student Mappings -->
                <div class="space-y-1" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="w-full flex items-center justify-between px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors {{ request()->routeIs('mappings.*') ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            <span class="font-medium">Student Mappings</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-8 space-y-1">
                        @if($facultySubjects->count() > 0)
                            @foreach($facultySubjects as $subject)
                            <a href="{{ route('mappings.index', $subject) }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-arrow-right w-3 h-3 mr-2"></i>
                                {{ $subject->subject_code }}
                            </a>
                            @endforeach
                            <a href="{{ route('subjects.index') }}" 
                               class="flex items-center px-4 py-2 text-xs text-gray-500 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-list w-3 h-3 mr-2"></i>
                                View all subjects
                            </a>
                        @else
                            <p class="px-4 py-2 text-xs text-gray-500">No subjects available</p>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="my-6 border-t border-gray-200"></div>
            
            <!-- Quick Stats -->
            <div class="px-4 mb-6">
                <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Quick Stats</h3>
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between px-4 py-2">
                        <span class="text-sm text-gray-600">Subjects</span>
                        <span class="text-sm font-medium text-gray-900">{{ $facultySubjects->count() }}</span>
                    </div>
                    @php
                        $connectedCount = $facultySubjects->whereNotNull('gcr_class_id')->count();
                    @endphp
                    <div class="flex items-center justify-between px-4 py-2">
                        <span class="text-sm text-gray-600">GCR Connected</span>
                        <span class="text-sm font-medium text-gray-900">{{ $connectedCount }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="px-4">
                <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Quick Actions</h3>
                <div class="mt-3 space-y-1">
                    <a href="{{ route('subjects.create') }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-plus-circle w-4 h-4 mr-2"></i>
                        New Subject
                    </a>
                    <a href="{{ route('classroom.index') }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-sync w-4 h-4 mr-2"></i>
                        Sync Classroom
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- User Info -->
        <div class="absolute bottom-0 w-full p-4 border-t border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-3 flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ auth('faculty')->check() ? auth('faculty')->user()->name : 'Faculty Member' }}
                    </p>
                    <p class="text-xs text-gray-500 truncate">
                        {{ auth('faculty')->check() ? auth('faculty')->user()->email : '' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('auth.logout') }}" class="ml-2">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
         @click="sidebarOpen = false"
         x-cloak></div>
    
    <!-- Main content -->
    <div class="lg:ml-64">
        <!-- Top bar -->
        <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
            <button type="button" 
                    class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    @click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <!-- Separator -->
            <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true"></div>
            
            <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                <div class="flex items-center gap-x-4 lg:gap-x-6">
                    <!-- Breadcrumbs and Page title -->
                    <div>
                        @if(trim($__env->yieldContent('breadcrumbs')))
                            <nav class="flex" aria-label="Breadcrumb">
                                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                                    <li>
                                        <a href="{{ route('dashboard') }}" class="hover:text-gray-700">
                                            <i class="fas fa-home"></i>
                                        </a>
                                    </li>
                                    @yield('breadcrumbs')
                                </ol>
                            </nav>
                        @endif
                        <h1 class="text-xl font-semibold text-gray-900 {{ trim($__env->yieldContent('breadcrumbs')) ? 'mt-1' : '' }}">
                            @yield('page-title', 'Dashboard')
                        </h1>
                    </div>
                </div>
                
                <div class="flex items-center gap-x-4 lg:gap-x-6 ml-auto">
                    <!-- Notifications -->
                    <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">View notifications</span>
                        <i class="fas fa-bell text-lg"></i>
                    </button>
                    
                    <!-- Separator -->
                    <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true"></div>
                    
                    <!-- Profile dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" 
                                class="-m-1.5 flex items-center p-1.5"
                                @click="open = !open">
                            <span class="sr-only">Open user menu</span>
                            <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <span class="hidden lg:flex lg:items-center">
                                <span class="ml-4 text-sm font-semibold leading-6 text-gray-900">
                                    {{ auth('faculty')->check() ? auth('faculty')->user()->name : 'Faculty' }}
                                </span>
                                <i class="ml-2 fas fa-chevron-down text-gray-400 text-xs"></i>
                            </span>
                        </button>
                        
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5"
                             @click.away="open = false"
                             x-cloak>
                            <form method="POST" action="{{ route('auth.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-3 py-1 text-left text-sm leading-6 text-gray-900 hover:bg-gray-50">
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page content -->
        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>
    
    @yield('scripts')
</body>
</html>