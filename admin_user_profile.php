<?php 
require __DIR__ . '/session.php'; 
require __DIR__ . '/db.php';

// Access Control
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: home.php');
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId === 0) {
    header('Location: admin.php');
    exit;
}

// Fetch User Info
$uStmt = $pdo->prepare('SELECT username, email, created_at FROM users WHERE id = :uid');
$uStmt->execute([':uid' => $userId]);
$targetUser = $uStmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    die("User not found");
}

// Fetch User Categories
$ucStmt = $pdo->prepare('SELECT c.name AS name, uc.xp AS xp, uc.level AS level, uc.category_id as cid FROM user_categories uc JOIN categories c ON c.id = uc.category_id WHERE uc.user_id = :uid ORDER BY c.name');
$ucStmt->execute([':uid' => $userId]);
$uc = $ucStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total XP and Level
// Strategy: Get max value from both tables to ensure data integrity
$xpStmt = $pdo->prepare("SELECT SUM(xp) FROM user_categories WHERE user_id = :uid");
$xpStmt->execute([':uid' => $userId]);
$catXp = (int)($xpStmt->fetchColumn() ?: 0);

$taskStmt = $pdo->prepare("SELECT SUM(xp) FROM tasks WHERE user_id = :uid AND status = 'completed'");
$taskStmt->execute([':uid' => $userId]);
$taskXp = (int)($taskStmt->fetchColumn() ?: 0);

// Use the larger of the two values
$userTotalXp = max($catXp, $taskXp);

$overallLevel = 1 + intdiv($userTotalXp, 300);

// Fetch Completed Tasks Count
$pcStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :uid AND status = \'completed\'');
$pcStmt->execute([':uid' => $userId]);
$completedCount = (int)($pcStmt->fetchColumn() ?: 0);

// Fetch Pending Tasks Count
$pdStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :uid AND status = \'pending\'');
$pdStmt->execute([':uid' => $userId]);
$pendingCount = (int)($pdStmt->fetchColumn() ?: 0);

// Fetch Recent Activity
$recentStmt = $pdo->prepare('SELECT t.title, t.xp, c.name AS category, t.completed_at FROM tasks t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = :uid AND t.status = "completed" ORDER BY t.completed_at DESC LIMIT 10');
$recentStmt->execute([':uid' => $userId]);
$recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/layout/header.php';
?>

<main class="p-6 overflow-y-auto h-full">
    <div class="max-w-7xl mx-auto space-y-8">
        
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
    </div>
</main>

<?php require __DIR__ . '/layout/footer.php'; ?>
