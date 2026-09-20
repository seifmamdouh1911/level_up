<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);

// Calculate global level if logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../db.php';
    $uid = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT SUM(xp) as total_xp FROM user_categories WHERE user_id = :uid');
    $stmt->execute([':uid' => $uid]);
    $totalXp = (int)($stmt->fetchColumn() ?: 0);
    $currentLevel = 1 + intdiv($totalXp, 300);
} else {
    $currentLevel = 1;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LevelUp Dashboard</title>
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-slate-200 antialiased overflow-hidden">

<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-card border-r border-gray-800 flex-shrink-0 hidden md:flex flex-col transition-all duration-300" id="sidebar">
        <div class="p-6 flex items-center justify-between">
            <div class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary">
                <i class="fas fa-gamepad mr-2 text-primary"></i>LevelUp
            </div>
        </div>

        <nav class="flex-1 px-4 space-y-2 mt-4">
            <?php if (($_SESSION['role'] ?? 'user') !== 'admin'): ?>
            <a href="home.php" class="sidebar-link flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'home.php' ? 'active bg-indigo-500/10 text-white' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-home mr-3"></i>
                Dashboard
            </a>
            <a href="task.php" class="sidebar-link flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'task.php' ? 'active bg-indigo-500/10 text-white' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-tasks mr-3"></i>
                Tasks
            </a>
            <a href="categories.php" class="sidebar-link flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'categories.php' ? 'active bg-indigo-500/10 text-white' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-layer-group mr-3"></i>
                Categories
            </a>
            <a href="profile.php" class="sidebar-link flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'profile.php' ? 'active bg-indigo-500/10 text-white' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-user mr-3"></i>
                Profile
            </a>
            <a href="summary.php" class="sidebar-link flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'summary.php' ? 'active bg-indigo-500/10 text-white' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-chart-bar mr-3"></i>
                Summary
            </a>
            <?php endif; ?>
            
            <a href="about.php" class="sidebar-link flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'about.php' ? 'active bg-indigo-500/10 text-white' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fas fa-info-circle mr-3"></i>
                About
            </a>

            <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
            <div class="mt-8 mb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Admin
            </div>
            <a href="admin.php" class="sidebar-link flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $currentPage == 'admin.php' ? 'active bg-purple-500/10 text-purple-400' : 'text-slate-400 hover:text-purple-400'; ?>">
                <i class="fas fa-shield-alt mr-3"></i>
                Dashboard
            </a>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <div class="flex items-center gap-3 mb-4 px-2">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                    <p class="text-xs text-slate-400">Level <?php echo $currentLevel; ?></p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Mobile Header -->
        <header class="md:hidden bg-card border-b border-gray-800 p-4 flex items-center justify-between">
            <div class="text-xl font-bold text-white">LevelUp</div>
            <button id="mobileMenuBtn" class="text-slate-300 hover:text-white">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            <!-- Background Decoration -->
            <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-indigo-900/20 to-transparent pointer-events-none z-0"></div>
            
            <div class="relative z-10 max-w-7xl mx-auto">
