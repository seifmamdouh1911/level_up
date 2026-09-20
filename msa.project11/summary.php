<?php 
require __DIR__ . '/session.php'; 
require __DIR__ . '/db.php';

$userId = (int)$_SESSION['user_id'];

// Fetch User Categories
$ucStmt = $pdo->prepare('SELECT c.name AS name, uc.xp AS xp, uc.level AS level FROM user_categories uc JOIN categories c ON c.id = uc.category_id WHERE uc.user_id = :uid ORDER BY c.name');
$ucStmt->execute([':uid' => $userId]);
$uc = $ucStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total XP
$totalXp = 0;
foreach ($uc as $c) { $totalXp += (int)$c['xp']; }

// Prepare Monthly Data
$months = [];
$monthXp = [];
$monthGoal = [];
for ($i = 5; $i >= 0; $i--) {
    $label = date('F', strtotime("-$i months"));
    $months[] = $label;
    $startDate = date('Y-m-01', strtotime("-$i months")) . ' 00:00:00';
    $endDate = date('Y-m-t', strtotime("-$i months")) . ' 23:59:59';
    $stmt = $pdo->prepare('SELECT SUM(xp) AS s FROM tasks WHERE user_id = :uid AND status = \'completed\' AND completed_at BETWEEN :start AND :end');
    $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
    $sum = (int)($stmt->fetchColumn() ?: 0);
    $monthXp[] = $sum;
    $monthGoal[] = 400; // Example Goal
}

// Prepare Category Series Data
$catSeries = [];
foreach ($uc as $c) {
    $series = ['label' => $c['name'], 'data' => []];
    for ($i = 5; $i >= 0; $i--) {
        $startDate = date('Y-m-01', strtotime("-$i months")) . ' 00:00:00';
        $endDate = date('Y-m-t', strtotime("-$i months")) . ' 23:59:59';
        $stmt = $pdo->prepare('SELECT SUM(xp) AS s FROM tasks WHERE user_id = :uid AND status = \'completed\' AND category_id = (SELECT id FROM categories WHERE name = :name) AND completed_at BETWEEN :start AND :end');
        $stmt->execute([':uid' => $userId, ':name' => $c['name'], ':start' => $startDate, ':end' => $endDate]);
        $series['data'][] = (int)($stmt->fetchColumn() ?: 0);
    }
    $catSeries[] = $series;
}

require __DIR__ . '/layout/header.php';
?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-white">Monthly Summary</h1>
        <p class="text-slate-400">Track your progress and XP growth over time.</p>
    </div>
    <div class="glass-card px-6 py-3 rounded-xl flex items-center gap-4 border border-slate-700/50">
        <div>
            <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total XP</p>
            <p class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary"><?php echo number_format($totalXp); ?></p>
        </div>
        <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center">
            <i class="fas fa-chart-line text-primary"></i>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
    
    <!-- Monthly XP Chart -->
    <div class="glass-card p-6 rounded-2xl border border-slate-700/50">
        <h3 class="text-lg font-bold text-white mb-6">XP History</h3>
        <div class="relative h-64 w-full">
            <canvas id="xpChart"></canvas>
        </div>
    </div>

    <!-- Category Trends Chart -->
    <div class="glass-card p-6 rounded-2xl border border-slate-700/50">
        <h3 class="text-lg font-bold text-white mb-6">Category Trends</h3>
        <div class="relative h-64 w-full">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

</div>

<!-- Detailed Category Stats -->
<div class="mt-8">
    <h2 class="text-xl font-bold text-white mb-6">Category Breakdown</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php if (count($uc) === 0): ?>
            <div class="col-span-full text-center py-12 text-slate-500 bg-slate-800/30 rounded-2xl border border-dashed border-slate-700">
                No categories found.
            </div>
        <?php else: ?>
            <?php foreach ($uc as $c): 
                $cxp = (int)$c['xp'];
                $clevel = (int)$c['level'];
                $target = 300; 
                $progress = ($cxp % $target) / $target * 100;
            ?>
            <div class="glass-card p-5 rounded-xl border border-slate-700/50 hover:bg-slate-800/50 transition-colors">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-white truncate pr-2"><?php echo htmlspecialchars($c['name']); ?></h3>
                    <span class="bg-slate-800 text-slate-300 text-xs px-2 py-1 rounded-full border border-slate-700">LV <?php echo $clevel; ?></span>
                </div>
                <div class="flex items-end gap-1 mb-3">
                    <span class="text-2xl font-bold text-primary"><?php echo number_format($cxp); ?></span>
                    <span class="text-xs text-slate-500 mb-1">XP</span>
                </div>
                <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    window.SUMMARY_DATA = {
        months: <?php echo json_encode($months); ?>,
        monthXp: <?php echo json_encode($monthXp); ?>,
        monthGoal: <?php echo json_encode($monthGoal); ?>,
        categorySeries: <?php echo json_encode($catSeries); ?>
    };
</script>
<!-- Note: layout/footer.php usually closes main/body, but we need summary.js before that or after. 
     Since footer.php includes </body>, we should put script before it or rely on footer not blocking.
     Wait, footer.php closes </body>. So I should put scripts before requiring footer.php? 
     No, usually scripts go at the end of body. 
     layout/footer.php has the closing tags.
     So I should put the script BEFORE requiring footer.php? 
     Yes.
-->
<script src="summary.js?v=<?php echo time(); ?>"></script>

<?php require __DIR__ . '/layout/footer.php'; ?>
