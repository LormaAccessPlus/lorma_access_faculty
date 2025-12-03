<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Faculty Grading System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <h1 class="text-xl font-bold">Faculty Grading System</h1>
                @auth('faculty')
                    <div class="flex space-x-4">
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-200">Dashboard</a>
                        <a href="{{ route('subjects.index') }}" class="hover:text-blue-200">Subjects</a>
                        <a href="{{ route('classroom.index') }}" class="hover:text-blue-200">Google Classroom</a>
                        
                        <!-- Grade Matrix Dropdown -->
                        <div class="relative dropdown">
                            <button class="hover:text-blue-200 flex items-center space-x-1" onclick="toggleDropdown(event)">
                                <span>Grade Matrix</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div class="dropdown-menu hidden absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg z-50">
                                <a href="{{ route('grade-matrix.zero-based') }}" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">
                                    <i class="fas fa-table mr-2"></i>Zero-based Matrix
                                </a>
                                <a href="{{ route('grade-matrix.nursing') }}" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">
                                    <i class="fas fa-heartbeat mr-2"></i>Nursing Matrix
                                </a>
                                <a href="{{ route('grade-matrix.general-education') }}" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">
                                    <i class="fas fa-graduation-cap mr-2"></i>General Education Matrix
                                </a>
                                <a href="{{ route('grade-matrix.customized') }}" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">
                                    <i class="fas fa-cog mr-2"></i>Customized Matrix
                                </a>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>
            <div>
                @auth('faculty')
                    <span class="mr-4">{{ auth('faculty')->user()->name }}</span>
                    <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">Logout</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <main class="min-h-screen">
        @yield('content')
    </main>

    <script>
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdown = event.currentTarget.nextElementSibling;
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            const isClickInside = event.target.closest('.dropdown');
            
            if (!isClickInside) {
                dropdowns.forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });
            }
        });
    </script>

    @yield('scripts')
</body>
</html>