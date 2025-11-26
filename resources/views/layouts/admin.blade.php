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
<body class="bg-gray-50" x-data="{ sidebarOpen: false, sidebarHidden: false }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:static lg:flex lg:flex-shrink-0" 
             :class="{ 
                 '-translate-x-full': !sidebarOpen && !sidebarHidden, 
                 'translate-x-0': sidebarOpen,
                 'lg:-translate-x-full': sidebarHidden,
                 'lg:translate-x-0': !sidebarHidden
             }"
             x-show="!sidebarHidden || sidebarOpen"
             x-transition>
            
            <div class="flex flex-col w-64">
                <!-- Logo -->
                <div class="flex items-center justify-center h-16 px-4 text-white flex-shrink-0" style="background-color: #08695A;">
                    <i class="fas fa-graduation-cap text-2xl mr-3"></i>
                    <h1 class="text-lg font-bold">Lorma Access+</h1>
                </div>
                
                <!-- Navigation - Scrollable -->
                <nav class="flex-1 overflow-y-auto mt-8">
                    <div class="px-4 space-y-2">
                        <!-- Dashboard -->
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:text-white transition-colors {{ request()->routeIs('dashboard') ? 'text-white border-r-2' : 'hover:bg-opacity-90' }}"
                           style="{{ request()->routeIs('dashboard') ? 'background-color: #08695A; border-color: #08695A;' : '' }}"
                           onmouseover="if (!this.classList.contains('text-white')) this.style.backgroundColor='#08695A'; if (!this.classList.contains('text-white')) this.style.color='white';"
                           onmouseout="if (!this.classList.contains('text-white')) this.style.backgroundColor=''; if (!this.classList.contains('text-white')) this.style.color='';">
                            <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                        
                        <!-- Subject Mapping -->
                        <a href="{{ route('subjects.mapping.index') }}" 
                           class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:text-white transition-colors {{ request()->routeIs('subjects.mapping.*') ? 'text-white border-r-2' : 'hover:bg-opacity-90' }}"
                           style="{{ request()->routeIs('subjects.mapping.*') ? 'background-color: #08695A; border-color: #08695A;' : '' }}"
                           onmouseover="if (!this.classList.contains('text-white')) this.style.backgroundColor='#08695A'; if (!this.classList.contains('text-white')) this.style.color='white';"
                           onmouseout="if (!this.classList.contains('text-white')) this.style.backgroundColor=''; if (!this.classList.contains('text-white')) this.style.color='';">
                            <i class="fas fa-link w-5 h-5 mr-3"></i>
                            <span class="font-medium">Subject Mapping</span>
                            <span class="ml-2 px-2 py-1 text-xs rounded-full" style="background-color: rgba(8, 105, 90, 0.2); color: #08695A;">New</span>
                        </a>
                        
                        <!-- My Subjects -->
                        <a href="{{ route('subjects.index') }}" 
                           class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:text-white transition-colors {{ request()->routeIs('subjects.*') && !request()->routeIs('subjects.mapping.*') ? 'text-white border-r-2' : 'hover:bg-opacity-90' }}"
                           style="{{ request()->routeIs('subjects.*') && !request()->routeIs('subjects.mapping.*') ? 'background-color: #08695A; border-color: #08695A;' : '' }}"
                           onmouseover="if (!this.classList.contains('text-white')) this.style.backgroundColor='#08695A'; if (!this.classList.contains('text-white')) this.style.color='white';"
                           onmouseout="if (!this.classList.contains('text-white')) this.style.backgroundColor=''; if (!this.classList.contains('text-white')) this.style.color='';">
                            <i class="fas fa-book w-5 h-5 mr-3"></i>
                            <span class="font-medium">My Subjects</span>
                        </a>
                        
                        <!-- Activities -->
                        <a href="{{ route('activities.index') }}" 
                           class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:text-white transition-colors {{ request()->routeIs('activities.*') ? 'text-white border-r-2' : 'hover:bg-opacity-90' }}"
                           style="{{ request()->routeIs('activities.*') ? 'background-color: #08695A; border-color: #08695A;' : '' }}"
                           onmouseover="if (!this.classList.contains('text-white')) this.style.backgroundColor='#08695A'; if (!this.classList.contains('text-white')) this.style.color='white';"
                           onmouseout="if (!this.classList.contains('text-white')) this.style.backgroundColor=''; if (!this.classList.contains('text-white')) this.style.color='';">
                            <i class="fas fa-tasks w-5 h-5 mr-3"></i>
                            <span class="font-medium">Activities</span>
                        </a>
                        
                        <!-- Google Classroom -->
                        <div class="space-y-1">
                            <a href="{{ route('classroom.index') }}" 
                               class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:text-white transition-colors {{ request()->routeIs('classroom.*') ? 'text-white border-r-2' : 'hover:bg-opacity-90' }}"
                               style="{{ request()->routeIs('classroom.*') ? 'background-color: #08695A; border-color: #08695A;' : '' }}"
                               onmouseover="if (!this.classList.contains('text-white')) this.style.backgroundColor='#08695A'; if (!this.classList.contains('text-white')) this.style.color='white';"
                               onmouseout="if (!this.classList.contains('text-white')) this.style.backgroundColor=''; if (!this.classList.contains('text-white')) this.style.color='';">
                                <i class="fab fa-google-drive w-5 h-5 mr-3"></i>
                                <span class="font-medium">Google Classroom</span>
                            </a>
                        </div>
                        
                        <!-- Student Mapping -->
                        <a href="{{ route('student-mapping.index') }}" 
                           class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:text-white transition-colors {{ request()->routeIs('student-mapping.*') || request()->routeIs('mappings.*') ? 'text-white border-r-2' : 'hover:bg-opacity-90' }}"
                           style="{{ request()->routeIs('student-mapping.*') || request()->routeIs('mappings.*') ? 'background-color: #08695A; border-color: #08695A;' : '' }}"
                           onmouseover="if (!this.classList.contains('text-white')) this.style.backgroundColor='#08695A'; if (!this.classList.contains('text-white')) this.style.color='white';"
                           onmouseout="if (!this.classList.contains('text-white')) this.style.backgroundColor=''; if (!this.classList.contains('text-white')) this.style.color='';">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            <span class="font-medium">Student Mapping</span>
                        </a>

                        <!-- Grading System -->
                        <a href="{{ route('grading-system.index') }}" 
                           class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:text-white transition-colors {{ request()->routeIs('grading-system.*') || request()->routeIs('grades.*') ? 'text-white border-r-2' : 'hover:bg-opacity-90' }}"
                           style="{{ request()->routeIs('grading-system.*') || request()->routeIs('grades.*') ? 'background-color: #08695A; border-color: #08695A;' : '' }}"
                           onmouseover="if (!this.classList.contains('text-white')) this.style.backgroundColor='#08695A'; if (!this.classList.contains('text-white')) this.style.color='white';"
                           onmouseout="if (!this.classList.contains('text-white')) this.style.backgroundColor=''; if (!this.classList.contains('text-white')) this.style.color='';">
                            <i class="fas fa-calculator w-5 h-5 mr-3"></i>
                            <span class="font-medium">Grading System</span>
                        </a>
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
                                $totalStudents = $facultySubjects->sum(function($subject) { return $subject->studentMappings->count(); });
                            @endphp
                            <div class="flex items-center justify-between px-4 py-2">
                                <span class="text-sm text-gray-600">GCR Connected</span>
                                <span class="text-sm font-medium text-gray-900">{{ $connectedCount }}</span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2">
                                <span class="text-sm text-gray-600">Total Students</span>
                                <span class="text-sm font-medium text-gray-900">{{ $totalStudents }}</span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2">
                                <span class="text-sm text-gray-600">DB Status</span>
                                <span id="db-status" class="text-sm font-medium">
                                    <i class="fas fa-circle text-gray-400"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="px-4 pb-6">
                        <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Quick Actions</h3>
                        <div class="mt-3 space-y-1">
                            <a href="{{ route('subjects.mapping.index') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-link w-4 h-4 mr-2"></i>
                                Map Subject
                            </a>
                            <a href="{{ route('classroom.index') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-sync w-4 h-4 mr-2"></i>
                                Sync Classroom
                            </a>
                            <button onclick="testGradeConnections()" 
                                    class="flex items-center w-full px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-database w-4 h-4 mr-2"></i>
                                Test DB Connections
                            </button>
                        </div>
                    </div>
                </nav>
                
                <!-- User Info -->
                <div class="flex-shrink-0 w-full p-4 border-t border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ auth('faculty')->check() ? auth('faculty')->user()->name  : 'Faculty Member' }}
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
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Top bar -->
            <div class="flex-shrink-0 flex h-16 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile sidebar toggle -->
                <button type="button" 
                        class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                        @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Desktop sidebar toggle - ALWAYS VISIBLE -->
                <button type="button" 
                        id="sidebarToggleBtn"
                        class="hidden lg:block p-2.5 text-gray-700 hover:text-white transition-all rounded-lg border-2"
                        :class="sidebarHidden ? 'bg-teal-600 text-white border-teal-600' : 'border-teal-600 hover:bg-teal-600'"
                        @click="sidebarHidden = !sidebarHidden"
                        title="Toggle Fullscreen (Hide Sidebar)">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                
                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200" aria-hidden="true"></div>
                
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
                    </div>
                </div>
            </div>
            
            <!-- Page content - Scrollable -->
            <main class="flex-1 overflow-y-auto py-6">
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
    </div>
    
    @yield('scripts')
    @stack('scripts')
    
    <!-- Global JavaScript Functions -->
    <script>
        // Sidebar toggle functionality
        let sidebarHidden = false;
        
        window.toggleSidebar = function() {
            const sidebar = document.querySelector('.fixed.inset-y-0.left-0.z-50.w-64');
            const mainContainer = document.querySelector('.flex.flex-col.flex-1');
            
            if (!sidebar || !mainContainer) {
                console.error('Sidebar or main container not found');
                return;
            }
            
            if (sidebarHidden) {
                // Show sidebar
                sidebar.style.transform = 'translateX(0)';
                sidebar.style.transition = 'transform 0.3s ease-in-out';
                mainContainer.style.marginLeft = '';
                mainContainer.style.transition = 'margin-left 0.3s ease-in-out';
                sidebarHidden = false;
            } else {
                // Hide sidebar
                sidebar.style.transform = 'translateX(-100%)';
                sidebar.style.transition = 'transform 0.3s ease-in-out';
                mainContainer.style.marginLeft = '0';
                mainContainer.style.transition = 'margin-left 0.3s ease-in-out';
                sidebarHidden = true;
            }
        };
        
        // Keyboard shortcut (Ctrl + B)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                window.toggleSidebar();
            }
        });
        
        // Global notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
            
            const bgColor = {
                'success': 'bg-green-50 border border-green-200 text-green-800',
                'error': 'bg-red-50 border border-red-200 text-red-800',
                'warning': 'bg-yellow-50 border border-yellow-200 text-yellow-800',
                'info': 'bg-blue-50 border border-blue-200 text-blue-800'
            }[type] || 'bg-gray-50 border border-gray-200 text-gray-800';
            
            notification.className += ` ${bgColor}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>
