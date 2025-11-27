<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #08695A 0%, #0A7B6A 50%, #065A4A 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .google-btn {
            background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
            transition: all 0.3s ease;
        }
        .google-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(66, 133, 244, 0.4);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 1; }
            50% { transform: scale(1); opacity: 0.7; }
            100% { transform: scale(0.95); opacity: 1; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <!-- Background Pattern -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/5 rounded-full"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-white/5 rounded-full"></div>
        <div class="absolute top-1/4 left-1/4 w-32 h-32 bg-white/5 rounded-full floating"></div>
        <div class="absolute bottom-1/4 right-1/4 w-24 h-24 bg-white/5 rounded-full floating" style="animation-delay: 1s;"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">
        <!-- Logo/Brand Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-xl mb-4 pulse-ring">
                <i class="fas fa-graduation-cap text-4xl text-teal-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">LORMA Colleges</h1>
            <p class="text-teal-100 text-lg">Faculty Grading System</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-2xl shadow-2xl p-8">
            <!-- Welcome Message -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome Back!</h2>
                <p class="text-gray-600">Sign in to access your grading dashboard</p>
            </div>

            <!-- Error/Success Messages -->
            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-start">
                    <i class="fas fa-exclamation-circle mt-0.5 mr-3 text-red-500"></i>
                    <div>
                        <p class="font-medium">Authentication Error</p>
                        <p class="text-sm">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-start">
                    <i class="fas fa-check-circle mt-0.5 mr-3 text-green-500"></i>
                    <div>
                        <p class="font-medium">Success</p>
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Google Sign In Button -->
            <a href="{{ route('auth.google') }}" 
               class="google-btn w-full flex justify-center items-center px-6 py-4 rounded-xl text-white font-semibold text-lg shadow-lg">
                <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign in with Google
            </a>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500">Institutional Access Only</span>
                </div>
            </div>

            <!-- Info Section -->
            <div class="bg-teal-50 rounded-xl p-4 border border-teal-100">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-teal-600 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-teal-800">Access Requirements</h3>
                        <ul class="mt-2 text-sm text-teal-700 space-y-1">
                            <li class="flex items-center">
                                <i class="fas fa-check text-teal-500 mr-2 text-xs"></i>
                                Must use @lorma.edu email account
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-teal-500 mr-2 text-xs"></i>
                                Faculty members only
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-teal-500 mr-2 text-xs"></i>
                                Google Classroom integration enabled
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-teal-100 text-sm">
            <p>&copy; {{ date('Y') }} LORMA Colleges. All rights reserved.</p>
            <p class="mt-1 text-teal-200/70">Faculty Grading System v1.0</p>
        </div>
    </div>
</body>
</html>
