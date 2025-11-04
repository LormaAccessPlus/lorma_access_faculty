<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Faculty Grading System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    @yield('scripts')
</body>
</html>