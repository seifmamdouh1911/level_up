<?php 
require __DIR__ . '/session.php'; 
require __DIR__ . '/db.php';

$userId = (int)$_SESSION['user_id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add existing category to user
    if ($action === 'add' && isset($_POST['category_id'])) {
        $cid = (int)$_POST['category_id'];
        $exists = $pdo->prepare('SELECT id FROM user_categories WHERE user_id = :uid AND category_id = :cid');
        $exists->execute([':uid' => $userId, ':cid' => $cid]);
        if (!$exists->fetch()) {
            $pdo->prepare('INSERT INTO user_categories (user_id, category_id, xp, level) VALUES (:uid,:cid,0,1)')
                ->execute([':uid' => $userId, ':cid' => $cid]);
        }
        header('Location: categories.php');
        exit;
    }
    
    // Create new category
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name !== '') {
            $find = $pdo->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:n)');
            $find->execute([':n' => $name]);
            $cid = (int)($find->fetchColumn() ?: 0);
            if ($cid === 0) {
                $ins = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:n, :d)');
                $ins->execute([':n' => $name, ':d' => $desc === '' ? null : $desc]);
                $cid = (int)$pdo->lastInsertId();
            }
            $exists = $pdo->prepare('SELECT id FROM user_categories WHERE user_id = :uid AND category_id = :cid');
            $exists->execute([':uid' => $userId, ':cid' => $cid]);
            if (!$exists->fetch()) {
                $pdo->prepare('INSERT INTO user_categories (user_id, category_id, xp, level) VALUES (:uid,:cid,0,1)')
                    ->execute([':uid' => $userId, ':cid' => $cid]);
            }
        }
        header('Location: categories.php');
        exit;
    }
}

// Fetch Data
$userCatsStmt = $pdo->prepare('SELECT c.id AS cid, c.name AS name, uc.xp AS xp, uc.level AS level FROM user_categories uc JOIN categories c ON c.id = uc.category_id WHERE uc.user_id = :uid ORDER BY c.name');
$userCatsStmt->execute([':uid' => $userId]);
$userCats = $userCatsStmt->fetchAll(PDO::FETCH_ASSOC);

$availableStmt = $pdo->prepare('SELECT id, name FROM categories WHERE id NOT IN (SELECT category_id FROM user_categories WHERE user_id = :uid) ORDER BY name');
$availableStmt->execute([':uid' => $userId]);
$availableCats = $availableStmt->fetchAll(PDO::FETCH_ASSOC);

$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$detail = null;

if ($cid > 0) {
    $dStmt = $pdo->prepare('SELECT c.id AS cid, c.name AS name, uc.xp AS xp, uc.level AS level FROM user_categories uc JOIN categories c ON c.id = uc.category_id WHERE uc.user_id = :uid AND c.id = :cid');
    $dStmt->execute([':uid' => $userId, ':cid' => $cid]);
    $detail = $dStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// For the modal
$cats = $userCats; 

require __DIR__ . '/layout/header.php';
?>

<?php if ($detail): ?>
    <!-- Detail View -->
    <?php
        $cxp = (int)$detail['xp'];
        $clevel = (int)$detail['level'];
        $ctarget = 300;
        $cprogress = $cxp % $ctarget;
        $cpercent = $ctarget > 0 ? (int)floor(($cprogress / $ctarget) * 100) : 0;
        
        $tStmt = $pdo->prepare('SELECT id, title, xp, status, created_at, completed_at FROM tasks WHERE user_id = :uid AND category_id = :cid ORDER BY created_at DESC');
        $tStmt->execute([':uid' => $userId, ':cid' => $cid]);
        $tasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);
        $active = array_filter($tasks, fn($t) => $t['status'] !== 'completed');
        $completed = array_filter($tasks, fn($t) => $t['status'] === 'completed');
    ?>

    <div class="mb-8 fade-in">
        <a href="categories.php" class="text-slate-400 hover:text-white mb-4 inline-block transition-colors"><i class="fas fa-arrow-left mr-2"></i> Back to Categories</a>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2"><?php echo htmlspecialchars($detail['name']); ?></h1>
                <p class="text-slate-400">Level <?php echo $clevel; ?> • <?php echo $cxp; ?> XP Total</p>
            </div>
            <button id="addTaskBtn" class="bg-primary hover:bg-indigo-600 text-white px-6 py-3 rounded-xl transition-all shadow-lg shadow-primary/25 flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i> Add Task
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="glass-card p-6 rounded-2xl mb-8 fade-in" style="animation-delay: 0.1s;">
        <div class="flex justify-between items-end mb-2">
            <span class="text-slate-400 text-sm font-medium">Level Progress</span>
            <span class="text-primary font-bold"><?php echo $cprogress; ?> / <?php echo $ctarget; ?> XP</span>
        </div>
        <div class="w-full bg-slate-700 h-4 rounded-full overflow-hidden">
            <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full transition-all duration-1000" style="width: <?php echo $cpercent; ?>%"></div>
        </div>
        <div class="grid grid-cols-3 gap-4 mt-6 text-center">
            <div>
                <div class="text-2xl font-bold text-white"><?php echo count($tasks); ?></div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">Total Tasks</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-white"><?php echo count($completed); ?></div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">Completed</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-white"><?php echo count($active); ?></div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">Pending</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 fade-in" style="animation-delay: 0.2s;">
        <!-- Active Tasks -->
        <div>
            <h2 class="text-xl font-semibold text-white mb-4">Active Tasks</h2>
            <div class="space-y-3">
                <?php if (empty($active)): ?>
                    <div class="text-center py-12 bg-slate-800/30 rounded-2xl border border-dashed border-slate-700 text-slate-500">
                        <i class="fas fa-check-circle text-4xl mb-3 opacity-20"></i>
                        <p>No active tasks in this category.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($active as $t): ?>
                        <div class="glass-card p-4 rounded-xl group hover:border-slate-600 transition-all" data-id="<?php echo (int)$t['id']; ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <button onclick="toggleTask(<?php echo (int)$t['id']; ?>, this)" class="w-5 h-5 rounded border-2 border-slate-500 hover:border-primary transition-colors"></button>
                                    <span class="text-slate-200 group-hover:text-white transition-colors"><?php echo htmlspecialchars($t['title']); ?></span>
                                </div>
                                <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded">+<?php echo (int)$t['xp']; ?> XP</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completed Tasks -->
        <div>
            <h2 class="text-xl font-semibold text-white mb-4">Completed History</h2>
            <div class="space-y-3">
                <?php if (empty($completed)): ?>
                    <div class="text-center py-12 text-slate-600">
                        <p>No completed tasks yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($completed as $t): ?>
                        <div class="p-4 rounded-xl bg-slate-800/30 border border-transparent opacity-75" data-id="<?php echo (int)$t['id']; ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 rounded bg-green-500/20 flex items-center justify-center">
                                        <i class="fas fa-check text-xs text-green-500"></i>
                                    </div>
                                    <span class="text-slate-500 line-through"><?php echo htmlspecialchars($t['title']); ?></span>
                                </div>
                                <span class="text-xs font-bold text-slate-600">+<?php echo (int)$t['xp']; ?> XP</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pass category ID to JS for the modal -->
    <script>
        window.CURRENT_CATEGORY_ID = <?php echo (int)$cid; ?>;
        // Simple toggle function since we aren't loading full task.js logic here
        async function toggleTask(id, btn) {
            const card = btn.closest('div[data-id]');
            card.style.opacity = '0.5';
            const form = new FormData();
            form.append("action", "toggle_complete");
            form.append("task_id", id);
            await fetch("task.php", { method: "POST", body: form });
            window.location.reload();
        }
    </script>

<?php else: ?>
    <!-- List View -->
    <div class="mb-8 fade-in">
        <h1 class="text-3xl font-bold text-white mb-2">Categories</h1>
        <p class="text-slate-400">Organize your life into areas of focus.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in" style="animation-delay: 0.1s;">
        <!-- Create/Add Column -->
        <div class="space-y-6">
            <!-- Create New -->
            <div class="glass-card p-6 rounded-2xl">
                <h3 class="text-lg font-semibold text-white mb-4">Create New Category</h3>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="create">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Name</label>
                        <input name="name" type="text" placeholder="e.g. Fitness" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Description</label>
                        <input name="description" type="text" placeholder="Optional" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-indigo-600 text-white py-2 rounded-lg transition-colors font-medium">
                        Create Category
                    </button>
                </form>
            </div>

            <!-- Add Existing -->
            <?php if (count($availableCats) > 0): ?>
            <div class="glass-card p-6 rounded-2xl">
                <h3 class="text-lg font-semibold text-white mb-4">Add Existing Category</h3>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Select Category</label>
                        <select name="category_id" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-colors">
                            <option value="">Choose...</option>
                            <?php foreach ($availableCats as $ac): ?>
                                <option value="<?php echo (int)$ac['id']; ?>"><?php echo htmlspecialchars($ac['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white py-2 rounded-lg transition-colors font-medium">
                        Add to My List
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Categories Grid -->
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (empty($userCats)): ?>
                <div class="col-span-full text-center py-12 text-slate-500">
                    <i class="fas fa-folder-open text-4xl mb-4 opacity-20"></i>
                    <p>You haven't added any categories yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($userCats as $uc): ?>
                    <?php
                        $cxp = (int)$uc['xp'];
                        $clevel = (int)$uc['level'];
                        $cpercent = ($cxp % 300) / 300 * 100;
                    ?>
                    <div class="glass-card p-6 rounded-2xl group cursor-pointer hover:border-slate-500 transition-all relative overflow-hidden" onclick="window.location.href='categories.php?cid=<?php echo (int)$uc['cid']; ?>'">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-layer-group text-6xl text-white"></i>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-xl font-bold text-white mb-1 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($uc['name']); ?></h3>
                            <p class="text-sm text-slate-400 mb-4">Level <?php echo $clevel; ?> • <?php echo $cxp; ?> XP</p>
                            
                            <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden mb-2">
                                <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full" style="width: <?php echo $cpercent; ?>%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Progress</span>
                                <span><?php echo (int)$cpercent; ?>%</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout/modal_add_task.php'; ?>

<script>
    // Pre-select category in modal if in detail view
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof window.CURRENT_CATEGORY_ID !== 'undefined') {
            const select = document.getElementById('taskCategory');
            if(select) select.value = window.CURRENT_CATEGORY_ID;
        }
    });

    // Modal Logic (reused from home.js/task.js - simplified inline if needed or rely on home.js if included)
    // Since we didn't include home.js or task.js here, we need the modal logic.
    // Let's include task.js logic but modified or just the modal parts.
    // Actually, let's just include the modal script here inline or a simple version.
    
    const modal = document.getElementById('taskModal');
    const openBtn = document.getElementById('addTaskBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const saveBtn = document.getElementById('saveTaskBtn');
    const overlay = document.getElementById('modalOverlay');

    const toggleModal = (show) => {
        if(modal) {
            if (show) {
                modal.classList.remove('hidden');
                setTimeout(() => document.getElementById('taskTitle').focus(), 50);
            } else {
                modal.classList.add('hidden');
            }
        }
    };

    if (openBtn) openBtn.onclick = () => toggleModal(true);
    if (closeBtn) closeBtn.onclick = () => toggleModal(false);
    if (overlay) overlay.onclick = () => toggleModal(false);

    if (saveBtn) {
        saveBtn.onclick = async () => {
            const title = document.getElementById('taskTitle').value;
            const xp = document.getElementById('taskXP').value;
            const categoryId = document.getElementById('taskCategory').value;

            if (!title) {
                alert('Please enter a task title');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const form = new FormData();
            form.append("action", "create");
            form.append("title", title);
            form.append("xp", xp);
            if (categoryId) form.append("category_id", categoryId);

            try {
                const res = await fetch("task.php", { method: "POST", body: form });
                const data = await res.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to create task');
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Create Task';
            }
        };
    }
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>