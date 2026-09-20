<?php 
require __DIR__ . '/session.php'; 
require __DIR__ . '/db.php';

$userId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $email === '') {
        $error = 'Username and Email are required.';
    } else {
        try {
            // Check if email taken by another user
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :e AND id != :uid');
            $check->execute([':e' => $email, ':uid' => $userId]);
            if ($check->fetch()) {
                $error = 'Email is already in use.';
            } else {
                if ($password !== '') {
                    // Update with password
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare('UPDATE users SET username = :u, email = :e, password_hash = :p WHERE id = :uid');
                    $upd->execute([':u' => $username, ':e' => $email, ':p' => $hash, ':uid' => $userId]);
                } else {
                    // Update without password
                    $upd = $pdo->prepare('UPDATE users SET username = :u, email = :e WHERE id = :uid');
                    $upd->execute([':u' => $username, ':e' => $email, ':uid' => $userId]);
                }
                
                // Update Session
                $_SESSION['username'] = $username;
                $message = 'Profile updated successfully!';
            }
        } catch (Exception $e) {
            $error = 'An error occurred.';
        }
    }
}

// Fetch User Data for Form
$userStmt = $pdo->prepare('SELECT username, email FROM users WHERE id = :uid');
$userStmt->execute([':uid' => $userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

// Fetch User Categories
$ucStmt = $pdo->prepare('SELECT c.name AS name, uc.xp AS xp, uc.level AS level, uc.category_id as cid FROM user_categories uc JOIN categories c ON c.id = uc.category_id WHERE uc.user_id = :uid ORDER BY c.name');
$ucStmt->execute([':uid' => $userId]);
$uc = $ucStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total XP and Level
$totalXp = 0;
foreach ($uc as $c) { $totalXp += (int)$c['xp']; }
$overallLevel = 1 + intdiv($totalXp, 300);

// Fetch Completed Tasks Count
$pcStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :uid AND status = \'completed\'');
$pcStmt->execute([':uid' => $userId]);
$completedCount = (int)($pcStmt->fetchColumn() ?: 0);

// Fetch Pending Tasks Count (Streak Logic Placeholder)
$pdStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :uid AND status = \'pending\'');
$pdStmt->execute([':uid' => $userId]);
$pendingCount = (int)($pdStmt->fetchColumn() ?: 0);

// Fetch Recent Activity
$recentStmt = $pdo->prepare('SELECT t.title, t.xp, c.name AS category, t.completed_at FROM tasks t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = :uid AND t.status = "completed" ORDER BY t.completed_at DESC LIMIT 5');
$recentStmt->execute([':uid' => $userId]);
$recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/layout/header.php';
?>

<main class="p-6 overflow-y-auto h-full">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="bg-green-500/10 border border-green-500/50 text-green-400 p-4 rounded-xl flex items-center gap-3 fade-in">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-xl flex items-center gap-3 fade-in">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Header / Profile Overview -->
        <div class="glass-card rounded-3xl p-8 relative overflow-hidden mb-8">
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-primary/20 to-secondary/20"></div>
            <div class="relative flex flex-col md:flex-row items-end md:items-center gap-6 mt-12">
                <div class="w-32 h-32 rounded-full border-4 border-slate-900 shadow-xl overflow-hidden bg-slate-800">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random&size=256" alt="Profile" class="w-full h-full object-cover">
                </div>
                <div class="flex-1 mb-2">
                    <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h1>
                    <p class="text-slate-400">"Keep leveling up your life!"</p>
                </div>
                <div class="flex gap-3 mt-4 md:mt-0">
                    <button onclick="document.getElementById('editProfileModal').classList.remove('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors font-medium border border-slate-700">
                        <i class="fas fa-edit mr-2"></i> Edit Profile
                    </button>
                    <button onclick="window.location.href='leaderboard.php'" class="px-4 py-2 bg-primary hover:bg-indigo-600 text-white rounded-lg transition-colors font-medium shadow-lg shadow-indigo-500/20">
                        <i class="fas fa-trophy mr-2"></i> Leaderboard
                    </button>
                </div>
            </div>

            <!-- Level Bar -->
            <div class="mt-8">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-white font-bold">Level <?php echo (int)$overallLevel; ?></span>
                    <span class="text-slate-400"><?php echo (int)$totalXp; ?> XP Total</span>
                </div>
                <div class="w-full h-4 bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary to-secondary relative" style="width: <?php echo min(100, ($totalXp % 300) / 3); ?>%">
                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                </div>
                <p class="text-xs text-right text-slate-500 mt-1"><?php echo 300 - ($totalXp % 300); ?> XP to next level</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
            <div class="glass-card p-6 rounded-2xl flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center text-yellow-400 text-xl">
                    <i class="fas fa-medal"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-sm">Badges Earned</p>
                    <p class="text-2xl font-bold text-white">0</p>
                </div>
            </div>
        </div>

        <!-- Content Split -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Category Skills -->
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">Skill Progress</h2>
                    <a href="categories.php" class="text-sm text-primary hover:text-indigo-400">View All</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (count($uc) === 0): ?>
                        <div class="col-span-2 text-center py-12 text-slate-500 bg-slate-800/30 rounded-2xl border border-dashed border-slate-700">
                            No categories yet. Complete tasks to earn XP!
                        </div>
                    <?php else: ?>
                        <?php foreach ($uc as $c): 
                            $cxp = (int)$c['xp'];
                            $clevel = (int)$c['level'];
                            $target = 300; // Assuming 300 per level for now
                            $progress = ($cxp % $target) / $target * 100;
                        ?>
                        <div class="glass-card p-5 rounded-xl border border-slate-700/50 hover:border-primary/50 transition-colors">
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
                <h2 class="text-xl font-bold text-white">Recent Activity</h2>
                <div class="glass-card p-6 rounded-2xl space-y-6">
                    <?php if (count($recent) === 0): ?>
                        <p class="text-slate-500 text-center text-sm">No recent activity.</p>
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
                
                <!-- About Card -->
                <div class="glass-card p-6 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 border-indigo-500/20">
                    <h3 class="font-bold text-white mb-2">About Me</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        "I’m focusing on health, productivity, and leveling up every day!"
                    </p>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="document.getElementById('editProfileModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-card border border-slate-700 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <div class="bg-card px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-500/10 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-user-edit text-primary"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">Edit Profile</h3>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" name="action" value="update_profile">
                                <div>
                                    <label class="block text-sm font-medium text-slate-400">Username</label>
                                    <input type="text" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required class="mt-1 block w-full bg-slate-800 border border-slate-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-400">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required class="mt-1 block w-full bg-slate-800 border border-slate-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-400">New Password (leave blank to keep current)</label>
                                    <input type="password" name="password" class="mt-1 block w-full bg-slate-800 border border-slate-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-700">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-indigo-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Save Changes
                    </button>
                    <button type="button" onclick="document.getElementById('editProfileModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-lg border border-slate-600 shadow-sm px-4 py-2 bg-transparent text-base font-medium text-slate-300 hover:text-white hover:bg-slate-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
