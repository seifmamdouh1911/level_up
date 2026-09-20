<?php 
require __DIR__ . '/session.php'; 
require __DIR__ . '/db.php';

$userId = (int)$_SESSION['user_id'];

// Fetch Stats
$catStmt = $pdo->prepare('SELECT c.id AS cid, c.name AS name, uc.xp AS xp, uc.level AS level FROM user_categories uc JOIN categories c ON uc.category_id = c.id WHERE uc.user_id = :uid ORDER BY c.name');
$catStmt->execute([':uid' => $userId]);
$cats = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$totalXp = 0;
foreach ($cats as $c) { $totalXp += (int)$c['xp']; }
$overallLevel = 1 + intdiv($totalXp, 300);
$levelTarget = 300;
$levelProgress = $totalXp % $levelTarget;
$levelPercent = $levelTarget > 0 ? (int)floor(($levelProgress / $levelTarget) * 100) : 0;

// Fetch Pending Tasks Count
$stmtCount = $pdo->prepare('SELECT COUNT(*) AS cnt FROM tasks WHERE user_id = :uid AND status = \'pending\'');
$stmtCount->execute([':uid' => $userId]);
$pendingTasks = (int)($stmtCount->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

// Fetch Completed Tasks Today (Mock logic for "today" as simple count for now or improve query)
$stmtCompleted = $pdo->prepare('SELECT COUNT(*) AS cnt FROM tasks WHERE user_id = :uid AND status = \'completed\'');
$stmtCompleted->execute([':uid' => $userId]);
$completedTasks = (int)($stmtCompleted->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

// Fetch Daily Activity Data (Last 7 Days)
$activityData = [];
$activityLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    $activityLabels[] = $label;
    
    $start = $date . ' 00:00:00';
    $end = $date . ' 23:59:59';
    
    $stmt = $pdo->prepare('SELECT SUM(xp) as total_xp FROM tasks WHERE user_id = :uid AND status = \'completed\' AND completed_at BETWEEN :start AND :end');
    $stmt->execute([':uid' => $userId, ':start' => $start, ':end' => $end]);
    $xp = (int)($stmt->fetchColumn() ?: 0);
    $activityData[] = $xp;
}

require __DIR__ . '/layout/header.php';
?>

<!-- Welcome Section -->
<div class="mb-8 fade-in">
    <h1 class="text-3xl font-bold text-white mb-2">Welcome back, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>!</h1>
    <p class="text-slate-400">Here's what's happening with your progress today.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in" style="animation-delay: 0.1s;">
    <!-- Level Card -->
    <div class="glass-card p-6 rounded-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
            <i class="fas fa-trophy text-6xl text-primary"></i>
        </div>
        <h3 class="text-slate-400 text-sm font-medium mb-1">Current Level</h3>
        <div class="text-3xl font-bold text-white mb-2"><?php echo (int)$overallLevel; ?></div>
        <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden">
            <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full" style="width: <?php echo $levelPercent; ?>%"></div>
        </div>
        <p class="text-xs text-slate-400 mt-2"><?php echo $levelProgress; ?> / <?php echo $levelTarget; ?> XP to next level</p>
    </div>

    <!-- XP Card -->
    <div class="glass-card p-6 rounded-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-400 text-sm font-medium">Total XP</h3>
            <div class="w-10 h-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-primary">
                <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white"><?php echo number_format($totalXp); ?></div>
        <p class="text-xs text-green-400 mt-1 flex items-center">
            <i class="fas fa-arrow-up mr-1"></i> Top 5% of users
        </p>
    </div>

    <!-- Tasks Pending -->
    <div class="glass-card p-6 rounded-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-400 text-sm font-medium">Pending Tasks</h3>
            <div class="w-10 h-10 rounded-full bg-orange-500/10 flex items-center justify-center text-orange-400">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white"><?php echo $pendingTasks; ?></div>
        <p class="text-xs text-slate-400 mt-1">Tasks waiting for you</p>
    </div>

    <!-- Tasks Completed -->
    <div class="glass-card p-6 rounded-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-400 text-sm font-medium">Completed</h3>
            <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-green-400">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-white"><?php echo $completedTasks; ?></div>
        <p class="text-xs text-slate-400 mt-1">Lifetime completions</p>
    </div>
</div>

<!-- Main Grid: Activity & Categories -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in" style="animation-delay: 0.2s;">
    
    <!-- Left Column: Tasks & Chart -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Chart Section -->
        <div class="glass-card p-6 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-white">Activity Overview</h2>
                <select class="bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2">
                    <option>Last 7 Days</option>
                </select>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Today's Tasks -->
        <div class="glass-card p-6 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-white">Today's Tasks</h2>
                <button id="addTaskBtn" class="text-sm bg-primary hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-1"></i> Add Task
                </button>
            </div>
            
            <div class="tasks-container space-y-3" id="taskList">
                <!-- Loaded via JS -->
                <div class="text-center py-8 text-slate-500">
                    <i class="fas fa-circle-notch fa-spin mr-2"></i> Loading tasks...
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Categories -->
    <div class="lg:col-span-1">
        <div class="glass-card p-6 rounded-2xl h-full">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-white">Categories</h2>
                <a href="categories.php" class="text-sm text-primary hover:text-white transition-colors">View All</a>
            </div>

            <div class="space-y-4">
                <?php if (empty($cats)): ?>
                    <div class="text-center py-8 text-slate-500">
                        <p>No categories yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($cats, 0, 5) as $cat): 
                        $cxp = (int)$cat['xp'];
                        $clevel = (int)$cat['level'];
                        $cpercent = ($cxp % 300) / 300 * 100;
                    ?>
                    <div class="group p-4 bg-slate-800/50 hover:bg-slate-800 rounded-xl transition-all cursor-pointer border border-transparent hover:border-slate-600"
                         onclick="window.location.href='categories.php?cid=<?php echo $cat['cid']; ?>'">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium text-white group-hover:text-primary transition-colors"><?php echo htmlspecialchars($cat['name']); ?></h4>
                            <span class="text-xs font-bold bg-slate-700 text-slate-300 px-2 py-1 rounded">Lvl <?php echo $clevel; ?></span>
                        </div>
                        <div class="w-full bg-slate-700 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-secondary h-full rounded-full transition-all duration-500" style="width: <?php echo $cpercent; ?>%"></div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-slate-500">
                            <span><?php echo $cxp; ?> XP</span>
                            <span>Next: <?php echo 300 - ($cxp % 300); ?> XP</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<?php require __DIR__ . '/layout/modal_add_task.php'; ?>

<script>
    // Pass PHP data to JS
    window.USER_CATEGORIES = <?php echo json_encode(array_map(function($c){ return ['id'=>(int)$c['cid'],'name'=>$c['name']]; }, $cats)); ?>;
    
    // Pass Activity Data
    window.ACTIVITY_DATA = {
        labels: <?php echo json_encode($activityLabels); ?>,
        data: <?php echo json_encode($activityData); ?>
    };
</script>
<script src="home.js?v=<?php echo time(); ?>"></script>

<?php require __DIR__ . '/layout/footer.php'; ?>
