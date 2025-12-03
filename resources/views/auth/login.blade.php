<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.6s ease-out;
        }
        .animate-slideIn {
            animation: slideIn 0.6s ease-out;
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #08695A 0%, #0A7B6A 50%, #0C8D7A 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <!-- Decorative circles -->
    <div class="absolute top-10 left-10 w-72 h-72 bg-white opacity-10 rounded-full blur-3xl animate-float"></div>
    <div class="absolute bottom-10 right-10 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl animate-float" style="animation-delay: 1s;"></div>
    
    <div class="max-w-md w-full animate-fadeIn">
        <div class="glass-effect rounded-2xl shadow-2xl p-8 relative overflow-hidden">
            <!-- Decorative top bar -->
            <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(to right, #08695A, #0A7B6A, #0C8D7A);"></div>
            
            <!-- Logo/Icon -->
            <div class="text-center mb-8 animate-slideIn">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl shadow-lg mb-4 transform hover:scale-110 transition-transform duration-300" style="background: linear-gradient(135deg, #08695A, #0A7B6A);">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Faculty Grading System</h1>
                <p class="text-gray-600">Welcome back! Please sign in to continue</p>
            </div>

            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 animate-slideIn">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 animate-slideIn">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                <a href="{{ route('auth.google') }}" 
                   class="group w-full flex justify-center items-center px-6 py-4 border-2 border-gray-200 rounded-xl shadow-sm text-base font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-opacity-50 transition-all duration-300 transform hover:scale-105"
                   style="transition: all 0.3s ease;"
                   onmouseover="this.style.borderColor='#08695A'; this.style.boxShadow='0 10px 15px -3px rgba(8, 105, 90, 0.2)';"
                   onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='';"
                   onfocus="this.style.outline='none'; this.style.boxShadow='0 0 0 4px rgba(8, 105, 90, 0.3)';"
                   onblur="this.style.boxShadow='';"
                   >
                    <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span class="transition-colors" style="transition: color 0.3s ease;" onmouseover="this.style.color='#08695A';" onmouseout="this.style.color='';">Sign in with Google</span>
                </a>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Only <strong class="text-gray-800">@lorma.edu</strong> accounts are allowed</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white text-sm opacity-90">
            <p>© {{ date('Y') }} Lorma Colleges. All rights reserved.</p>
        </div>
    </div>
</body>
</html>