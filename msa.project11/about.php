<?php require __DIR__ . '/layout/header.php'; ?>

<!-- Header -->
<div class="text-center py-8 fade-in">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-primary to-secondary mb-6 shadow-lg shadow-indigo-500/30">
        <i class="fas fa-gamepad text-4xl text-white"></i>
    </div>
    <h1 class="text-4xl font-bold text-white mb-4">About LevelUp</h1>
    <p class="text-xl text-slate-400 max-w-2xl mx-auto">
        Turn your life into a game. Track habits, complete tasks, and level up your real-world skills.
    </p>
</div>

<!-- Mission Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 fade-in" style="animation-delay: 0.1s;">
    
    <!-- Who We Are -->
    <div class="glass-card p-8 rounded-2xl relative overflow-hidden group hover:bg-slate-800/50 transition-colors">
        <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fas fa-users text-8xl text-white"></i>
        </div>
        <div class="relative z-10">
            <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400 text-xl mb-4">
                <i class="fas fa-fingerprint"></i>
            </div>
            <h2 class="text-xl font-bold text-white mb-3">Who We Are</h2>
            <p class="text-slate-400 leading-relaxed">
                LevelUp is a personal growth and productivity system designed to help you
                track your habits, tasks, and category progress — all while leveling up like a game.
            </p>
        </div>
    </div>

    <!-- Our Mission -->
    <div class="glass-card p-8 rounded-2xl relative overflow-hidden group hover:bg-slate-800/50 transition-colors">
        <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fas fa-bullseye text-8xl text-white"></i>
        </div>
        <div class="relative z-10">
            <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-400 text-xl mb-4">
                <i class="fas fa-rocket"></i>
            </div>
            <h2 class="text-xl font-bold text-white mb-3">Our Mission</h2>
            <p class="text-slate-400 leading-relaxed">
                Our mission is simple: make your real life feel like an RPG where every action
                gives you XP and every improvement levels you up. We help you stay motivated, focused, and aware of your progress.
            </p>
        </div>
    </div>

</div>

<!-- Features Section -->
<div class="glass-card p-8 rounded-2xl border border-slate-700/50 mb-8 fade-in" style="animation-delay: 0.2s;">
    <h2 class="text-2xl font-bold text-white mb-8 text-center">What You Can Do</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700 text-center">
            <div class="w-10 h-10 mx-auto rounded-full bg-green-500/20 flex items-center justify-center text-green-400 mb-3">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="font-semibold text-white mb-1">Track Tasks</h3>
            <p class="text-xs text-slate-400">Daily task management</p>
        </div>
        <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700 text-center">
            <div class="w-10 h-10 mx-auto rounded-full bg-yellow-500/20 flex items-center justify-center text-yellow-400 mb-3">
                <i class="fas fa-star"></i>
            </div>
            <h3 class="font-semibold text-white mb-1">Earn XP</h3>
            <p class="text-xs text-slate-400">Level up your skills</p>
        </div>
        <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700 text-center">
            <div class="w-10 h-10 mx-auto rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 mb-3">
                <i class="fas fa-chart-pie"></i>
            </div>
            <h3 class="font-semibold text-white mb-1">View Stats</h3>
            <p class="text-xs text-slate-400">Detailed analytics</p>
        </div>
        <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700 text-center">
            <div class="w-10 h-10 mx-auto rounded-full bg-pink-500/20 flex items-center justify-center text-pink-400 mb-3">
                <i class="fas fa-heart"></i>
            </div>
            <h3 class="font-semibold text-white mb-1">Build Habits</h3>
            <p class="text-xs text-slate-400">Consistency is key</p>
        </div>
    </div>
</div>

<!-- Why We Built It -->
<div class="glass-card p-8 rounded-2xl bg-gradient-to-r from-indigo-500/10 to-purple-500/10 border-indigo-500/20 text-center fade-in" style="animation-delay: 0.3s;">
    <h2 class="text-xl font-bold text-white mb-4">Why We Built LevelUp</h2>
    <p class="text-slate-300 max-w-2xl mx-auto leading-relaxed">
        Most productivity apps feel boring. LevelUp makes growth fun, visual, and rewarding — 
        so you stay consistent and motivated to become the best version of yourself.
    </p>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
