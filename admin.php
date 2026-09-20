<?php
require __DIR__ . '/session.php';
require __DIR__ . '/db.php';

// Access Control
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: home.php');
    exit;
}

// Determine View Mode
$viewUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// ==================================================================================
// LOGIC: DELETE USER (Global)
// ==================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
        $uidToDelete = (int)$_POST['user_id'];
        // Prevent deleting self
        if ($uidToDelete !== (int)$_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $uidToDelete]);
            // If we were viewing that user, go back to dashboard
            if ($viewUserId === $uidToDelete) {
                header('Location: admin.php');
                exit;
            }
            // If we were on dashboard, reload dashboard
            if ($viewUserId === 0) {
                 header('Location: admin.php');
                 exit;
            }
        }
    }
}

// ==================================================================================
// LOGIC: DATA FETCHING
// ==================================================================================

if ($viewUserId > 0) {
    // --- PROFILE VIEW LOGIC ---
    
    // Fetch User Info
    $uStmt = $pdo->prepare('SELECT username, email, created_at FROM users WHERE id = :uid');
    $uStmt->execute([':uid' => $viewUserId]);
    $targetUser = $uStmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        // User not found, redirect to dashboard
        header('Location: admin.php');
        exit;
    }

    // Fetch User Categories
    $ucStmt = $pdo->prepare('SELECT c.name AS name, uc.xp AS xp, uc.level AS level, uc.category_id as cid FROM user_categories uc JOIN categories c ON c.id = uc.category_id WHERE uc.user_id = :uid ORDER BY c.name');
    $ucStmt->execute([':uid' => $viewUserId]);
    $uc = $ucStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Total XP and Level
    // Strategy: Get max value from both tables to ensure data integrity
    $xpStmt = $pdo->prepare("SELECT SUM(xp) FROM user_categories WHERE user_id = :uid");
    $xpStmt->execute([':uid' => $viewUserId]);
    $catXp = (int)($xpStmt->fetchColumn() ?: 0);

    $taskStmt = $pdo->prepare("SELECT SUM(xp) FROM tasks WHERE user_id = :uid AND status = 'completed'");
    $taskStmt->execute([':uid' => $viewUserId]);
    $taskXp = (int)($taskStmt->fetchColumn() ?: 0);

    // Use the larger of the two values
    $userTotalXp = max($catXp, $taskXp);
    $overallLevel = 1 + intdiv($userTotalXp, 300);

    // Fetch Completed Tasks Count
    $pcStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :uid AND status = \'completed\'');
    $pcStmt->execute([':uid' => $viewUserId]);
    $completedCount = (int)($pcStmt->fetchColumn() ?: 0);

    // Fetch Pending Tasks Count
    $pdStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :uid AND status = \'pending\'');
    $pdStmt->execute([':uid' => $viewUserId]);
    $pendingCount = (int)($pdStmt->fetchColumn() ?: 0);

    // Fetch Recent Activity
    $recentStmt = $pdo->prepare('SELECT t.title, t.xp, c.name AS category, t.completed_at FROM tasks t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = :uid AND t.status = "completed" ORDER BY t.completed_at DESC LIMIT 10');
    $recentStmt->execute([':uid' => $viewUserId]);
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // --- DASHBOARD VIEW LOGIC ---

    // Fetch Users
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.created_at, u.role,
               (SELECT COUNT(*) FROM tasks t WHERE t.user_id = u.id) as task_count,
               COALESCE((SELECT SUM(xp) FROM user_categories uc WHERE uc.user_id = u.id), 0) as total_xp
        FROM users u
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // System Analytics
    $totalUsers = count($users);
    $totalTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $totalXp = (int)$pdo->query("SELECT SUM(xp) FROM user_categories")->fetchColumn();
    $avgLevel = $totalUsers > 0 ? floor(((int)$pdo->query("SELECT SUM(level) FROM user_categories")->fetchColumn() ?: 0) / $totalUsers) : 1;

    // Recent Global Activity
    $activityStmt = $pdo->query("
        SELECT t.title, t.xp, u.username, t.completed_at 
        FROM tasks t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.status = 'completed' 
        ORDER BY t.completed_at DESC 
        LIMIT 5
    ");
    $globalActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

    // Category Distribution
    $catDistStmt = $pdo->query("SELECT c.name, COUNT(t.id) as count FROM categories c LEFT JOIN tasks t ON t.category_id = c.id GROUP BY c.id ORDER BY count DESC");
    $catDist = $catDistStmt->fetchAll(PDO::FETCH_ASSOC);

    // User Growth (Last 7 Days)
    $growthStmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $userGrowth = $growthStmt->fetchAll(PDO::FETCH_ASSOC);
}

require __DIR__ . '/layout/header.php';
?>

<main class="p-6 overflow-y-auto h-full">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <?php if ($viewUserId > 0): ?>
            <!-- ========================================== -->
            <!-- VIEW: USER PROFILE                         -->
            <!-- ========================================== -->
            
            <!-- Navigation -->
            <div class="fade-in">
                <a href="admin.php" class="text-slate-400 hover:text-white inline-flex items-center gap-2 transition-colors">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <!-- Header / Profile Overview -->
            <div class="glass-card rounded-3xl p-8 relative overflow-hidden mb-8 fade-in" style="animation-delay: 0.1s;">
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-purple-500/20 to-indigo-500/20"></div>
                <div class="relative flex flex-col md:flex-row items-end md:items-center gap-6 mt-12">
                    <div class="w-32 h-32 rounded-full border-4 border-slate-900 shadow-xl overflow-hidden bg-slate-800">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($targetUser['username']); ?>&background=random&size=256" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 mb-2">
                        <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($targetUser['username']); ?></h1>
                        <p class="text-slate-400"><?php echo htmlspecialchars($targetUser['email']); ?></p>
                        <p class="text-xs text-slate-500 mt-1">Joined: <?php echo date('F j, Y', strtotime($targetUser['created_at'])); ?></p>
                    </div>
                </div>

                <!-- Level Bar -->
                <div class="mt-8">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-white font-bold">Level <?php echo (int)$overallLevel; ?></span>
                        <span class="text-slate-400"><?php echo number_format($userTotalXp); ?> XP Total</span>
                    </div>
                    <div class="w-full h-4 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-primary to-secondary relative" style="width: <?php echo min(100, ($userTotalXp % 300) / 3); ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 fade-in" style="animation-delay: 0.2s;">
                <div class="glass-card p-6 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center text-green-400 text-xl">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">Tasks Completed</p>
                        <p class="text-2xl font-bold text-white"><?php echo $completedCount; ?></p>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center text-orange-400 text-xl">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">Active Pending</p>
                        <p class="text-2xl font-bold text-white"><?php echo $pendingCount; ?></p>
                    </div>
                </div>
            </div>

            <!-- Content Split -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in" style="animation-delay: 0.3s;">
                
                <!-- Category Skills -->
                <div class="lg:col-span-2 space-y-6">
                    <h2 class="text-xl font-bold text-white">Skill Progress</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if (count($uc) === 0): ?>
                            <div class="col-span-2 text-center py-12 text-slate-500 bg-slate-800/30 rounded-2xl border border-dashed border-slate-700">
                                User has no category progress yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($uc as $c): 
                                $cxp = (int)$c['xp'];
                                $clevel = (int)$c['level'];
                                $target = 300; 
                                $progress = ($cxp % $target) / $target * 100;
                            ?>
                            <div class="glass-card p-5 rounded-xl border border-slate-700/50">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-bold text-white"><?php echo htmlspecialchars($c['name']); ?></h3>
                                        <p class="text-xs text-slate-400">Level <?php echo $clevel; ?></p>
                                    </div>
                                    <span class="text-xs font-mono text-primary bg-primary/10 px-2 py-1 rounded"><?php echo $cxp; ?> XP</span>
                                </div>
                                <div class="w-full bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full rounded-full" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="space-y-6">
                    <h2 class="text-xl font-bold text-white">Recent Activity Log</h2>
                    <div class="glass-card p-6 rounded-2xl space-y-6">
                        <?php if (count($recent) === 0): ?>
                            <p class="text-slate-500 text-center text-sm">No recent activity found.</p>
                        <?php else: ?>
                            <?php foreach ($recent as $r): ?>
                            <div class="flex gap-4 relative pl-4 border-l-2 border-slate-700">
                                <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-slate-800 border-2 border-primary"></div>
                                <div>
                                    <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($r['title']); ?></h4>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <span class="text-primary"><?php echo htmlspecialchars($r['category'] ?? 'General'); ?></span> • +<?php echo (int)$r['xp']; ?> XP
                                    </p>
                                    <p class="text-[10px] text-slate-600 mt-1"><?php echo date('M j, H:i', strtotime($r['completed_at'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        <?php else: ?>
            <!-- ========================================== -->
            <!-- VIEW: DASHBOARD                            -->
            <!-- ========================================== -->

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 fade-in">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">Admin Dashboard</h1>
                    <p class="text-slate-400">Manage users and view their progress.</p>
                </div>
                <div class="glass-card px-4 py-2 rounded-xl flex items-center gap-2 border border-purple-500/30 bg-purple-500/10">
                    <i class="fas fa-shield-alt text-purple-400"></i>
                    <span class="text-purple-200 font-medium">Administrator Mode</span>
                </div>
            </div>

            <!-- System Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 fade-in" style="animation-delay: 0.1s;">
                <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-blue-500/20 bg-blue-500/5">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400 text-xl">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">Total Users</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($totalUsers); ?></p>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-green-500/20 bg-green-500/5">
                    <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center text-green-400 text-xl">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">Tasks Completed</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($totalTasks); ?></p>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-yellow-500/20 bg-yellow-500/5">
                    <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center text-yellow-400 text-xl">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">System XP</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($totalXp); ?></p>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-2xl flex items-center gap-4 border border-pink-500/20 bg-pink-500/5">
                    <div class="w-12 h-12 rounded-xl bg-pink-500/20 flex items-center justify-center text-pink-400 text-xl">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">Avg User Level</p>
                        <p class="text-2xl font-bold text-white"><?php echo $avgLevel; ?></p>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 fade-in" style="animation-delay: 0.15s;">
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-white mb-4">Task Category Distribution</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-white mb-4">New User Registrations (Last 7 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Users Table -->
                <div class="lg:col-span-2 glass-card rounded-2xl overflow-hidden fade-in" style="animation-delay: 0.2s;">
                    <div class="p-6 border-b border-slate-700/50">
                        <h2 class="text-lg font-bold text-white">Registered Users</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-800/30 text-slate-400 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-4 font-semibold">User</th>
                                    <th class="px-6 py-4 font-semibold">Role</th>
                                    <th class="px-6 py-4 font-semibold text-center">Tasks</th>
                                    <th class="px-6 py-4 font-semibold text-right">XP</th>
                                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50">
                                <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold mr-3">
                                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($u['username']); ?></div>
                                                <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($u['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="px-2 py-0.5 inline-flex text-[10px] font-semibold rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20">ADMIN</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 inline-flex text-[10px] font-semibold rounded-full bg-slate-800 text-slate-400 border border-slate-700">USER</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-300">
                                        <?php echo (int)$u['task_count']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-primary">
                                        <?php echo number_format((int)$u['total_xp']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="admin.php?user_id=<?php echo $u['id']; ?>" class="text-slate-400 hover:text-white mr-3" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" class="inline-block" onsubmit="return confirm('Delete this user?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="text-slate-500 hover:text-red-400 transition-colors" title="Delete User">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Global Activity Feed -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.3s;">
                    <h2 class="text-lg font-bold text-white mb-6">Live System Activity</h2>
                    <div class="space-y-6">
                        <?php if (empty($globalActivity)): ?>
                            <p class="text-slate-500 text-center text-sm">No activity recorded yet.</p>
                        <?php else: ?>
                            <?php foreach ($globalActivity as $act): ?>
                            <div class="relative pl-6 border-l-2 border-slate-700 pb-2 last:pb-0">
                                <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-slate-800 border-2 border-primary"></div>
                                <div>
                                    <p class="text-sm text-white">
                                        <span class="font-bold text-primary"><?php echo htmlspecialchars($act['username']); ?></span> 
                                        completed 
                                        <span class="text-slate-300">"<?php echo htmlspecialchars($act['title']); ?>"</span>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        +<?php echo (int)$act['xp']; ?> XP • <?php echo date('M j, H:i', strtotime($act['completed_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
            <script>
                // Data for Charts
                const catLabels = <?php echo json_encode(array_column($catDist, 'name')); ?>;
                const catData = <?php echo json_encode(array_column($catDist, 'count')); ?>;
                
                const growthLabels = <?php echo json_encode(array_column($userGrowth, 'date')); ?>;
                const growthData = <?php echo json_encode(array_column($userGrowth, 'count')); ?>;

                document.addEventListener("DOMContentLoaded", () => {
                    // Category Pie Chart
                    new Chart(document.getElementById('categoryChart'), {
                        type: 'doughnut',
                        data: {
                            labels: catLabels,
                            datasets: [{
                                data: catData,
                                backgroundColor: [
                                    '#6366f1', '#ec4899', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'right', labels: { color: '#94a3b8' } }
                            }
                        }
                    });

                    // Growth Line Chart
                    new Chart(document.getElementById('growthChart'), {
                        type: 'line',
                        data: {
                            labels: growthLabels,
                            datasets: [{
                                label: 'New Users',
                                data: growthData,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', stepSize: 1 } },
                                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/layout/footer.php'; ?>
