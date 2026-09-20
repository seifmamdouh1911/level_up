<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LevelUp</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: '#0f172a',
                        card: '#1e293b',
                        primary: '#6366f1',
                        secondary: '#ec4899',
                        accent: '#06b6d4'
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(236, 72, 153, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(99, 102, 241, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-enter {
            animation: slideIn 0.3s ease-out forwards;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hidden-form {
            display: none;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 text-slate-200 font-sans">

    <div class="w-full max-w-md">
        
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-primary to-secondary mb-4 shadow-lg shadow-indigo-500/30">
                <i class="fas fa-gamepad text-3xl text-white"></i>
            </div>
            <h1 class="text-4xl font-bold text-white tracking-tight">LevelUp</h1>
            <p class="text-slate-400 mt-2">Gamify your life, achieve your goals.</p>
        </div>

        <!-- Card -->
        <div class="glass-card rounded-2xl p-8 shadow-2xl relative overflow-hidden">
            
            <!-- Error Message Container -->
            <div id="errorMessage" class="hidden mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-red-200 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText">Error message here</span>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-6 form-enter" action="javascript:void(0);">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-1">Welcome Back</h2>
                    <p class="text-slate-400 text-sm">Enter your credentials to continue</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-slate-500"></i>
                            </div>
                            <input id="loginEmail" type="email" required 
                                class="block w-full pl-10 pr-3 py-2.5 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-white placeholder-slate-500 transition-all outline-none"
                                placeholder="name@example.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-500"></i>
                            </div>
                            <input id="loginPassword" type="password" required
                                class="block w-full pl-10 pr-3 py-2.5 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-white placeholder-slate-500 transition-all outline-none"
                                placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all shadow-lg shadow-indigo-500/30">
                    Sign In
                    <i class="fas fa-arrow-right ml-2 mt-1"></i>
                </button>

                <div class="text-center text-sm text-slate-400">
                    Don't have an account? 
                    <button type="button" onclick="showRegister()" class="text-primary hover:text-indigo-400 font-medium ml-1 transition-colors">
                        Create account
                    </button>
                </div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="space-y-6 hidden-form form-enter" action="javascript:void(0);">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-1">Create Account</h2>
                    <p class="text-slate-400 text-sm">Join us and start leveling up</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-slate-500"></i>
                            </div>
                            <input id="regUser" type="text" required
                                class="block w-full pl-10 pr-3 py-2.5 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-white placeholder-slate-500 transition-all outline-none"
                                placeholder="HeroName">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-slate-500"></i>
                            </div>
                            <input id="regEmail" type="email" required
                                class="block w-full pl-10 pr-3 py-2.5 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-white placeholder-slate-500 transition-all outline-none"
                                placeholder="name@example.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-500"></i>
                            </div>
                            <input id="regPassword" type="password" required
                                class="block w-full pl-10 pr-3 py-2.5 bg-slate-800/50 border border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-white placeholder-slate-500 transition-all outline-none"
                                placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-secondary hover:bg-pink-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary transition-all shadow-lg shadow-pink-500/30">
                    Get Started
                    <i class="fas fa-rocket ml-2 mt-1"></i>
                </button>

                <div class="text-center text-sm text-slate-400">
                    Already have an account? 
                    <button type="button" onclick="showLogin()" class="text-primary hover:text-indigo-400 font-medium ml-1 transition-colors">
                        Sign in
                    </button>
                </div>
            </form>

        </div>

        <p class="text-center text-slate-500 text-xs mt-8">
            &copy; <?php echo date('Y'); ?> LevelUp. All rights reserved.
        </p>
    </div>

    <script src="login.js?v=<?php echo time(); ?>"></script>
</body>
</html>
